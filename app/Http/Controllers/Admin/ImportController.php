<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessUsersCsvImport;
use App\Models\CsvImport;
use App\Models\User;
use App\Models\Votacion;
use App\Models\Asamblea;
use App\Services\LocationResolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    /**
     * Mostrar progreso de una importación específica
     */
    public function show(CsvImport $import): Response
    {
        $import->load(['votacion', 'asamblea', 'createdBy']);
        
        return Inertia::render('Admin/Imports/Show', [
            'import' => $import,
        ]);
    }

    /**
     * Obtener estado actual de una importación (para polling)
     */
    public function status(CsvImport $import): JsonResponse
    {
        return response()->json([
            'id' => $import->id,
            'status' => $import->status,
            'progress_percentage' => $import->progress_percentage,
            'total_rows' => $import->total_rows,
            'processed_rows' => $import->processed_rows,
            'successful_rows' => $import->successful_rows,
            'failed_rows' => $import->failed_rows,
            'error_count' => $import->error_count,
            'errors' => $import->errors,
            'conflict_resolution' => $import->conflict_resolution, // INCLUIR CONFLICTOS
            'duration' => $import->duration,
            'is_active' => $import->is_active,
            'is_completed' => $import->is_completed,
            'is_failed' => $import->is_failed,
            'has_errors' => $import->has_errors,
            'started_at' => $import->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $import->completed_at?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Listar todas las importaciones de una votación
     */
    public function index(Votacion $votacion): Response
    {
        $imports = CsvImport::forVotacion($votacion->id)
            ->with(['createdBy'])
            ->recent()
            ->paginate(10);

        return Inertia::render('Admin/Imports/Index', [
            'votacion' => $votacion,
            'imports' => $imports,
        ]);
    }

    /**
     * Obtener importaciones recientes de una votación (para Tab 3)
     */
    public function recent(Votacion $votacion): JsonResponse
    {
        $imports = CsvImport::forVotacion($votacion->id)
            ->with(['createdBy'])
            ->recent()
            ->limit(5)
            ->get();

        return response()->json($imports);
    }

    /**
     * Obtener importación activa de una votación (para íconos de progreso)
     */
    public function active(Votacion $votacion): JsonResponse
    {
        $activeImport = CsvImport::forVotacion($votacion->id)
            ->active()
            ->first();

        return response()->json($activeImport);
    }

    /**
     * Mostrar listado general de importaciones de usuarios
     */
    public function indexGeneral(Request $request): Response
    {
        $imports = CsvImport::forGeneral()
            ->with(['createdBy'])
            ->recent()
            ->paginate(10);

        return Inertia::render('Admin/Imports/Index', [
            'imports' => $imports,
            'isGeneral' => true,
        ]);
    }

    /**
     * Mostrar formulario de creación de importación general
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Imports/Create');
    }

    /**
     * Analizar archivo CSV y devolver estructura para mapeo - OPTIMIZADO PARA ARCHIVOS GRANDES
     */
    public function analyze(Request $request): JsonResponse
    {
        // Aumentar límite de archivo a 50MB para archivos grandes
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:51200', // 50MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('csv_file');
            $filePath = $file->getPathname();
            $fileSize = $file->getSize();
            
            // Usar SplFileObject para lectura eficiente línea por línea
            $csvFile = new \SplFileObject($filePath, 'r');
            $csvFile->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            
            // Obtener headers (primera línea)
            $csvFile->rewind();
            $headers = $csvFile->current();
            
            if (empty($headers) || (is_array($headers) && count(array_filter($headers)) === 0)) {
                return response()->json(['error' => 'El archivo CSV está vacío o no tiene headers válidos.'], 400);
            }
            
            // Obtener muestra de datos (máximo 10 filas para preview)
            $sampleData = [];
            $maxSampleRows = 10;
            $rowCount = 0;
            
            $csvFile->next(); // Saltar header
            while (!$csvFile->eof() && $rowCount < $maxSampleRows) {
                $row = $csvFile->current();
                if (!empty($row) && is_array($row) && count(array_filter($row)) > 0) {
                    $sampleData[] = $row;
                    $rowCount++;
                }
                $csvFile->next();
            }
            
            // Estimación inteligente del total de filas basada en tamaño de archivo
            $estimatedRows = $this->estimateRowCount($filePath, $fileSize);
            
            // Campos disponibles del modelo User para mapeo
            $availableFields = [
                'name' => 'Nombre',
                'email' => 'Email',
                'documento_identidad' => 'Documento de Identidad',
                'tipo_documento' => 'Tipo de Documento',
                'telefono' => 'Teléfono',
                'direccion' => 'Dirección',
                'territorio_id' => 'ID Territorio',
                'departamento_id' => 'ID Departamento',
                'municipio_id' => 'ID Municipio',
                'localidad_id' => 'ID Localidad',
                'cargo_id' => 'ID Cargo',
                'es_miembro' => 'Es Miembro'
            ];
            
            return response()->json([
                'headers' => $headers,
                'sample_data' => $sampleData,
                'available_fields' => $availableFields,
                'total_rows' => $estimatedRows,
                'file_size' => $fileSize,
                'is_large_file' => $estimatedRows > 10000,
                'estimated' => $estimatedRows > 1000, // Indicar si es estimación
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al analizar archivo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Estimar número de filas basado en tamaño de archivo y muestra
     */
    private function estimateRowCount(string $filePath, int $fileSize): int
    {
        try {
            // Para archivos pequeños (<1MB), contar exactamente
            if ($fileSize < 1048576) { // 1MB
                $lineCount = 0;
                $file = new \SplFileObject($filePath, 'r');
                while (!$file->eof()) {
                    $file->current();
                    $lineCount++;
                    $file->next();
                }
                return max(0, $lineCount - 1); // Menos header
            }
            
            // Para archivos grandes, estimar basado en las primeras 1000 líneas
            $sampleSize = 1000;
            $file = new \SplFileObject($filePath, 'r');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            
            $sampleBytes = 0;
            $sampleLines = 0;
            
            // Saltar header
            $file->current();
            $file->next();
            
            while (!$file->eof() && $sampleLines < $sampleSize) {
                $currentPos = $file->ftell();
                $row = $file->current();
                
                if (!empty($row) && is_array($row)) {
                    $sampleLines++;
                    $sampleBytes = $file->ftell();
                }
                $file->next();
            }
            
            if ($sampleLines > 0 && $sampleBytes > 0) {
                // Calcular bytes promedio por línea
                $bytesPerLine = $sampleBytes / $sampleLines;
                
                // Estimar total de líneas
                $estimatedLines = intval($fileSize / $bytesPerLine);
                
                // Aplicar factor de corrección conservador
                return max(1, intval($estimatedLines * 0.95)); // 5% menos para ser conservador
            }
            
            // Fallback: estimación básica
            return max(1, intval($fileSize / 100)); // Asume ~100 bytes por línea
            
        } catch (\Exception $e) {
            Log::warning("Error estimando filas del CSV: " . $e->getMessage());
            // Fallback muy básico
            return max(1, intval($fileSize / 100));
        }
    }

    /**
     * Crear nueva importación de usuarios
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'csv_file' => 'required|file|mimes:csv,txt|max:51200', // 50MB para archivos grandes
            'import_mode' => 'required|in:insert,update,both',
            'field_mappings' => 'required|array',
            'update_fields' => 'nullable|array',
        ], [
            'name.required' => 'El nombre de la importación es requerido.',
            'csv_file.required' => 'Debe seleccionar un archivo CSV.',
            'csv_file.mimes' => 'El archivo debe ser un CSV válido.',
            'csv_file.max' => 'El archivo no puede exceder 50MB.',
            'import_mode.required' => 'Debe seleccionar un modo de importación.',
            'field_mappings.required' => 'Debe mapear al menos un campo.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }

        try {
            $file = $request->file('csv_file');
            $originalName = $file->getClientOriginalName();
            
            // Generar nombre único para el archivo
            $filename = time() . '_' . str_replace(' ', '_', $originalName);
            
            // Almacenar archivo en storage/app/imports/
            $filePath = $file->storeAs('imports', $filename);
            
            // Crear registro de importación
            $csvImport = CsvImport::create([
                'name' => $request->input('name'),
                'filename' => $filename,
                'original_filename' => $originalName,
                'import_type' => 'users',
                'import_mode' => $request->input('import_mode'),
                'field_mappings' => $request->input('field_mappings'),
                'update_fields' => $request->input('update_fields', []),
                'status' => 'pending',
                'batch_size' => config('app.csv_import_batch_size', 50),
                'created_by' => Auth::id(),
            ]);
            
            // Despachar job para procesar en background
            ProcessUsersCsvImport::dispatch($csvImport);
            
            return redirect()
                ->route('admin.imports.show', $csvImport)
                ->with('success', 'Importación iniciada. El archivo se está procesando en segundo plano.');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error al iniciar importación: ' . $e->getMessage());
        }
    }

    /**
     * Resolver un conflicto específico
     */
    public function resolveConflict(Request $request, CsvImport $import): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'conflict_id' => 'required|string',
            'resolution' => 'required|in:skip,update,merge,force_create',
            'merge_selections' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $conflictId = $request->input('conflict_id');
            $resolution = $request->input('resolution');
            
            $conflicts = $import->conflict_resolution ?? [];
            
            // Buscar el conflicto
            $conflictIndex = null;
            foreach ($conflicts as $index => $conflict) {
                if ($conflict['id'] === $conflictId) {
                    $conflictIndex = $index;
                    break;
                }
            }
            
            if ($conflictIndex === null) {
                return response()->json(['error' => 'Conflicto no encontrado.'], 404);
            }
            
            $conflict = $conflicts[$conflictIndex];
            
            // Procesar resolución
            switch ($resolution) {
                case 'skip':
                    // Simplemente marcar como resuelto sin hacer nada
                    $conflicts[$conflictIndex]['resolved'] = true;
                    $conflicts[$conflictIndex]['resolution'] = 'skipped';
                    break;
                    
                case 'update':
                    // Actualizar usuario existente
                    $this->processConflictUpdate($conflict);
                    $conflicts[$conflictIndex]['resolved'] = true;
                    $conflicts[$conflictIndex]['resolution'] = 'updated';
                    break;
                    
                case 'merge':
                    // Fusionar datos
                    $mergeSelections = $request->input('merge_selections');
                    $this->processConflictMerge($conflict, $mergeSelections);
                    $conflicts[$conflictIndex]['resolved'] = true;
                    $conflicts[$conflictIndex]['resolution'] = 'merged';
                    break;
                    
                case 'force_create':
                    // Forzar creación de nuevo usuario
                    $this->processConflictForceCreate($conflict);
                    $conflicts[$conflictIndex]['resolved'] = true;
                    $conflicts[$conflictIndex]['resolution'] = 'force_created';
                    break;
            }
            
            // Actualizar conflictos en la base de datos
            $import->update(['conflict_resolution' => $conflicts]);
            
            return response()->json([
                'success' => true,
                'message' => 'Conflicto resuelto exitosamente.',
                'resolved_conflicts' => array_filter($conflicts, fn($c) => $c['resolved'] ?? false),
                'pending_conflicts' => array_filter($conflicts, fn($c) => !($c['resolved'] ?? false)),
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al resolver conflicto: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Procesar resolución tipo 'update'
     */
    private function processConflictUpdate(array $conflict): void
    {
        $existingUser = null;
        
        if (isset($conflict['existing_user'])) {
            $existingUser = User::find($conflict['existing_user']['id']);
        } elseif (isset($conflict['existing_users']['by_email'])) {
            $existingUser = User::find($conflict['existing_users']['by_email']['id']);
        } elseif (isset($conflict['existing_users']['by_document'])) {
            $existingUser = User::find($conflict['existing_users']['by_document']['id']);
        }
        
        if ($existingUser) {
            // Usar data_with_ids para tener IDs resueltos
            $dataToUse = $conflict['data_with_ids'] ?? $conflict['data'];
            // Filtrar datos para preservar valores existentes cuando CSV está vacío
            $updateData = $this->filterConflictUpdateData($dataToUse, $existingUser);
            
            // Debug logging para conflictos UPDATE
            \Log::info("Conflict UPDATE Debug", [
                'usuario_id' => $existingUser->id,
                'datos_originales_csv' => $conflict['data'],
                'datos_filtrados_update' => $updateData,
                'valores_existentes_relevantes' => array_intersect_key($existingUser->toArray(), $conflict['data'])
            ]);
            
            if (!empty($updateData)) {
                $existingUser->update($updateData);
            }
            // Asignar a votación si aplica (aunque no haya actualización de datos)
            $this->assignUserToVotacionFromConflict($existingUser, $conflict);
        }
    }
    
    /**
     * Filtrar datos de conflicto para preservar valores existentes cuando CSV está vacío
     */
    private function filterConflictUpdateData(array $csvData, User $existingUser): array
    {
        $filteredData = [];
        
        foreach ($csvData as $field => $value) {
            // No actualizar password
            if ($field === 'password') {
                continue;
            }
            
            // Si el valor del CSV es null/vacío, preservar valor existente
            if ($this->isEmptyValue($value)) {
                // No incluir en updateData para mantener valor existente
                continue;
            }
            
            // Aplicar normalización para nombre y email
            if ($field === 'name') {
                $value = \Str::title(mb_strtolower(trim($value)));
            } elseif ($field === 'email') {
                $value = mb_strtolower(trim($value));
            }
            
            // Solo actualizar si el CSV tiene un valor válido
            $filteredData[$field] = $value;
        }
        
        return $filteredData;
    }
    
    /**
     * Verificar si un valor se considera vacío y no debe sobrescribir datos existentes
     */
    private function isEmptyValue($value): bool
    {
        return $value === null || 
               $value === '' || 
               (is_string($value) && trim($value) === '');
    }

    /**
     * Actualizar datos de existing_user en un conflicto con información fresca de la BD
     */
    public function refreshConflictData(Request $request, CsvImport $import)
    {
        try {
            $validator = Validator::make($request->all(), [
                'conflict_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Datos de solicitud inválidos'], 400);
            }

            $conflictId = $request->input('conflict_id');
            $conflicts = $import->conflict_resolution ?? [];
            
            $conflictIndex = array_search($conflictId, array_column($conflicts, 'id'));
            
            if ($conflictIndex === false) {
                return response()->json(['error' => 'Conflicto no encontrado'], 404);
            }

            $conflict = $conflicts[$conflictIndex];
            
            // Actualizar datos del existing_user con información fresca
            if (isset($conflict['existing_user'])) {
                $userId = $conflict['existing_user']['id'];
                $freshUser = User::find($userId);
                
                if ($freshUser) {
                    $userData = $freshUser->toArray();
                    
                    // Agregar nombres de ubicaciones para mostrar en frontend
                    $locationResolver = app(LocationResolverService::class);
                    $locationNames = $locationResolver->getLocationNames($userData);
                    $userData = array_merge($userData, $locationNames);
                    
                    $conflicts[$conflictIndex]['existing_user'] = $userData;
                }
            }
            
            // Actualizar datos de existing_users si existe
            if (isset($conflict['existing_users'])) {
                $locationResolver = app(LocationResolverService::class);
                
                if (isset($conflict['existing_users']['by_email'])) {
                    $userId = $conflict['existing_users']['by_email']['id'];
                    $freshUser = User::find($userId);
                    if ($freshUser) {
                        $userData = $freshUser->toArray();
                        $locationNames = $locationResolver->getLocationNames($userData);
                        $userData = array_merge($userData, $locationNames);
                        $conflicts[$conflictIndex]['existing_users']['by_email'] = $userData;
                    }
                }
                
                if (isset($conflict['existing_users']['by_document'])) {
                    $userId = $conflict['existing_users']['by_document']['id'];
                    $freshUser = User::find($userId);
                    if ($freshUser) {
                        $userData = $freshUser->toArray();
                        $locationNames = $locationResolver->getLocationNames($userData);
                        $userData = array_merge($userData, $locationNames);
                        $conflicts[$conflictIndex]['existing_users']['by_document'] = $userData;
                    }
                }
            }
            
            // Guardar conflictos actualizados
            $import->update(['conflict_resolution' => $conflicts]);
            
            return response()->json([
                'success' => true,
                'conflict' => $conflicts[$conflictIndex],
                'message' => 'Datos del conflicto actualizados'
            ]);
            
        } catch (Exception $e) {
            Log::error('Error al actualizar datos del conflicto: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Procesar resolución tipo 'merge'
     */
    private function processConflictMerge(array $conflict, ?array $mergeSelections = null): void
    {
        // Si no hay selecciones específicas, usar comportamiento por defecto
        if (!$mergeSelections) {
            $this->processConflictUpdate($conflict);
            return;
        }
        
        // Obtener usuario existente
        $existingUser = null;
        
        if (isset($conflict['existing_user'])) {
            $existingUser = User::find($conflict['existing_user']['id']);
        } elseif (isset($conflict['existing_users']['by_email'])) {
            $existingUser = User::find($conflict['existing_users']['by_email']['id']);
        } elseif (isset($conflict['existing_users']['by_document'])) {
            $existingUser = User::find($conflict['existing_users']['by_document']['id']);
        }
        
        if (!$existingUser) {
            return;
        }
        
        // Construir datos de actualización basado en las selecciones del usuario
        $mergedData = [];
        
        // USAR data_with_ids para tener IDs resueltos
        $dataToUse = $conflict['data_with_ids'] ?? $conflict['data'];
        
        foreach ($mergeSelections as $field => $source) {
            // En resolución de conflictos, solo excluir password
            // Email y documento_identidad SÍ se pueden actualizar en resolución de conflictos
            if ($field === 'password') {
                continue; // Solo excluir password
            }
            
            if ($source === 'csv' && isset($dataToUse[$field])) {
                $value = $dataToUse[$field];
                // Aplicar normalización para nombre y email
                if ($field === 'name' && !$this->isEmptyValue($value)) {
                    $value = \Str::title(mb_strtolower(trim($value)));
                } elseif ($field === 'email' && !$this->isEmptyValue($value)) {
                    $value = mb_strtolower(trim($value));
                }
                $mergedData[$field] = $value;
            } elseif ($source === 'existing' && isset($existingUser->$field)) {
                $mergedData[$field] = $existingUser->$field;
            }
        }
        
        // Log para debugging
        \Log::info("ProcessConflictMerge - Usuario: {$existingUser->id}", [
            'mergeSelections' => $mergeSelections,
            'mergedData' => $mergedData,
            'dataToUse' => $dataToUse
        ]);
        
        // Actualizar solo si hay datos para actualizar
        if (!empty($mergedData)) {
            $result = $existingUser->update($mergedData);
            \Log::info("Resultado de update: " . ($result ? 'SUCCESS' : 'FAILED'));
        } else {
            \Log::warning("No hay datos para actualizar");
        }
        // Asignar a votación si aplica (aunque no haya actualización de datos)
        $this->assignUserToVotacionFromConflict($existingUser, $conflict);
    }

    /**
     * Procesar resolución tipo 'force_create'
     */
    private function processConflictForceCreate(array $conflict): void
    {
        // USAR data_with_ids para tener IDs resueltos de ubicaciones
        $userData = $conflict['data_with_ids'] ?? $conflict['data'];
        $userData['password'] = bcrypt('temporal123');
        $userData['activo'] = true;
        
        // Normalizar nombre y email ANTES de modificar para duplicados
        if (isset($userData['name'])) {
            $userData['name'] = \Str::title(mb_strtolower(trim($userData['name'])));
        }
        if (isset($userData['email'])) {
            $userData['email'] = mb_strtolower(trim($userData['email']));
        }
        
        // Modificar email o documento para evitar duplicados
        if (isset($userData['email'])) {
            $userData['email'] = 'duplicado_' . time() . '_' . $userData['email'];
        }
        if (isset($userData['documento_identidad'])) {
            $userData['documento_identidad'] = $userData['documento_identidad'] . '_dup_' . time();
        }
        
        // LIMPIAR CAMPOS VACÍOS: Eliminar campos con strings vacíos
        $cleanedData = [];
        foreach ($userData as $key => $value) {
            // Solo incluir campos que NO estén vacíos
            if ($value !== '' && $value !== null && (!is_string($value) || trim($value) !== '')) {
                $cleanedData[$key] = $value;
            }
        }
        
        $newUser = User::create($cleanedData);
        // Asignar a votación si aplica
        $this->assignUserToVotacionFromConflict($newUser, $conflict);
    }

    /**
     * Crear nueva importación de usuarios para una votación específica
     */
    public function storeWithVotacion(Request $request, Votacion $votacion): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'csv_file' => 'required|file|mimes:csv,txt|max:51200', // 50MB para archivos grandes
            'import_mode' => 'required|in:insert,update,both',
            'field_mappings' => 'required|array',
            'update_fields' => 'nullable|array',
        ], [
            'name.required' => 'El nombre de la importación es requerido.',
            'csv_file.required' => 'Debe seleccionar un archivo CSV.',
            'csv_file.mimes' => 'El archivo debe ser un CSV válido.',
            'csv_file.max' => 'El archivo no puede exceder 50MB.',
            'import_mode.required' => 'Debe seleccionar un modo de importación.',
            'field_mappings.required' => 'Debe mapear al menos un campo.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }

        try {
            $file = $request->file('csv_file');
            $originalName = $file->getClientOriginalName();
            
            // Generar nombre único para el archivo
            $filename = time() . '_' . str_replace(' ', '_', $originalName);
            
            // Almacenar archivo en storage/app/imports/
            $filePath = $file->storeAs('imports', $filename);
            
            // Crear registro de importación con votacion_id
            $csvImport = CsvImport::create([
                'votacion_id' => $votacion->id,
                'name' => $request->input('name'),
                'filename' => $filename,
                'original_filename' => $originalName,
                'import_type' => 'votacion',
                'import_mode' => $request->input('import_mode'),
                'field_mappings' => $request->input('field_mappings'),
                'update_fields' => $request->input('update_fields', []),
                'status' => 'pending',
                'batch_size' => config('app.csv_import_batch_size', 50),
                'created_by' => Auth::id(),
            ]);
            
            // Despachar job para procesar en background
            ProcessUsersCsvImport::dispatch($csvImport);
            
            return redirect()
                ->route('admin.imports.show', $csvImport)
                ->with('success', 'Importación de votantes iniciada. El archivo se está procesando en segundo plano.')
                ->with('import_id', $csvImport->id);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error al iniciar importación: ' . $e->getMessage());
        }
    }

    /**
     * Crear nueva importación de usuarios para una asamblea específica
     */
    public function storeWithAsamblea(Request $request, Asamblea $asamblea): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'csv_file' => 'required|file|mimes:csv,txt|max:51200', // 50MB para archivos grandes
            'import_mode' => 'required|in:insert,update,both',
            'field_mappings' => 'required|array',
            'update_fields' => 'nullable|array',
        ], [
            'name.required' => 'El nombre de la importación es requerido.',
            'csv_file.required' => 'Debe seleccionar un archivo CSV.',
            'csv_file.mimes' => 'El archivo debe ser un CSV válido.',
            'csv_file.max' => 'El archivo no puede exceder 50MB.',
            'import_mode.required' => 'Debe seleccionar un modo de importación.',
            'field_mappings.required' => 'Debe mapear al menos un campo.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }

        try {
            $file = $request->file('csv_file');
            $originalName = $file->getClientOriginalName();
            
            // Generar nombre único para el archivo
            $filename = time() . '_' . str_replace(' ', '_', $originalName);
            
            // Almacenar archivo en storage/app/imports/
            $filePath = $file->storeAs('imports', $filename);
            
            // Crear registro de importación con asamblea_id
            $csvImport = CsvImport::create([
                'asamblea_id' => $asamblea->id,
                'name' => $request->input('name'),
                'filename' => $filename,
                'original_filename' => $originalName,
                'import_type' => 'asamblea',
                'import_mode' => $request->input('import_mode'),
                'field_mappings' => $request->input('field_mappings'),
                'update_fields' => $request->input('update_fields', []),
                'status' => 'pending',
                'batch_size' => config('app.csv_import_batch_size', 50),
                'created_by' => Auth::id(),
            ]);
            
            // Despachar job para procesar en background
            ProcessUsersCsvImport::dispatch($csvImport);
            
            // Log para debugging
            \Log::info('Importación de asamblea creada', [
                'import_id' => $csvImport->id,
                'asamblea_id' => $asamblea->id,
                'filename' => $filename
            ]);
            
            // Devolver respuesta igual que en votaciones
            return redirect()
                ->route('admin.imports.show', $csvImport)
                ->with('success', 'Importación de participantes iniciada. El archivo se está procesando en segundo plano.')
                ->with('import_id', $csvImport->id);
            
        } catch (\Exception $e) {
            \Log::error('Error en storeWithAsamblea: ' . $e->getMessage(), [
                'asamblea_id' => $asamblea->id,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Error al iniciar importación: ' . $e->getMessage());
        }
    }

    /**
     * Obtener importaciones recientes de una asamblea
     */
    public function recentForAsamblea(Asamblea $asamblea): JsonResponse
    {
        $imports = CsvImport::forAsamblea($asamblea->id)
            ->with(['createdBy'])
            ->recent()
            ->limit(5)
            ->get();

        return response()->json($imports);
    }

    /**
     * Obtener importación activa de una asamblea
     */
    public function activeForAsamblea(Asamblea $asamblea): JsonResponse
    {
        $activeImport = CsvImport::forAsamblea($asamblea->id)
            ->active()
            ->first();

        return response()->json($activeImport);
    }
    
    /**
     * Listar todas las importaciones de una asamblea
     */
    public function indexForAsamblea(Asamblea $asamblea): Response
    {
        $imports = CsvImport::forAsamblea($asamblea->id)
            ->with(['createdBy'])
            ->recent()
            ->paginate(10);

        return Inertia::render('Admin/Imports/Index', [
            'asamblea' => $asamblea,
            'imports' => $imports,
        ]);
    }
    
    /**
     * Asignar usuario a votación o asamblea desde un conflicto si la importación está asociada
     */
    private function assignUserToVotacionFromConflict(User $user, array $conflict): void
    {
        // Obtener el import_id del conflicto
        $importId = $conflict['import_id'] ?? null;
        if (!$importId) {
            return;
        }
        
        // Obtener la importación
        $import = CsvImport::find($importId);
        if (!$import) {
            return;
        }
        
        // Si es una importación de votación
        if ($import->votacion_id) {
            try {
                // Verificar si el usuario ya está asignado a la votación
                $existingAssignment = DB::table('votacion_usuario')
                    ->where('votacion_id', $import->votacion_id)
                    ->where('usuario_id', $user->id)
                    ->first();
                
                if (!$existingAssignment) {
                    // Obtener el tenant_id de la votación
                    $votacion = Votacion::find($import->votacion_id);
                    if (!$votacion) {
                        Log::error("Votación {$import->votacion_id} no encontrada para asignación desde conflicto");
                        return;
                    }
                    
                    // Asignar usuario a la votación
                    DB::table('votacion_usuario')->insert([
                        'votacion_id' => $import->votacion_id,
                        'usuario_id' => $user->id,
                        'tenant_id' => $votacion->tenant_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    Log::info("Usuario {$user->id} asignado a votación {$import->votacion_id} desde resolución de conflicto");
                }
                
            } catch (\Throwable $e) {
                Log::error("Error asignando usuario {$user->id} a votación desde conflicto: " . $e->getMessage());
            }
        }
        
        // Si es una importación de asamblea
        if ($import->asamblea_id) {
            try {
                // Verificar si el usuario ya está asignado a la asamblea
                $existingAssignment = DB::table('asamblea_usuario')
                    ->where('asamblea_id', $import->asamblea_id)
                    ->where('usuario_id', $user->id)
                    ->first();
                
                if (!$existingAssignment) {
                    // Obtener el tenant_id de la asamblea
                    $asamblea = Asamblea::find($import->asamblea_id);
                    if (!$asamblea) {
                        Log::error("Asamblea {$import->asamblea_id} no encontrada para asignación desde conflicto");
                        return;
                    }
                    
                    // Asignar usuario a la asamblea
                    DB::table('asamblea_usuario')->insert([
                        'asamblea_id' => $import->asamblea_id,
                        'usuario_id' => $user->id,
                        'tenant_id' => $asamblea->tenant_id,
                        'tipo_participacion' => 'asistente',
                        'asistio' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    Log::info("Usuario {$user->id} asignado a asamblea {$import->asamblea_id} desde resolución de conflicto");
                }
                
            } catch (\Throwable $e) {
                Log::error("Error asignando usuario {$user->id} a asamblea desde conflicto: " . $e->getMessage());
            }
        }
    }
}
