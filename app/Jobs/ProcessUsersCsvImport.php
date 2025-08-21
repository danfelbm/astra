<?php

namespace App\Jobs;

use App\Models\CsvImport;
use App\Models\User;
use App\Services\LocationResolverService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessUsersCsvImport implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;
    protected LocationResolverService $locationResolver;

    public function __construct(
        public CsvImport $csvImport
    ) {
        $this->locationResolver = app(LocationResolverService::class);
    }

    /**
     * Ejecutar el job principal
     */
    public function handle(): void
    {
        try {
            $this->csvImport->markAsProcessing();
            
            // Leer y validar archivo CSV
            $csvData = $this->readAndValidateCsv();
            if (!$csvData) {
                return;
            }
            
            // Obtener headers
            $headers = array_shift($csvData);
            $this->csvImport->update(['total_rows' => count($csvData)]);
            
            // Procesar en chunks
            $chunks = array_chunk($csvData, $this->csvImport->batch_size);
            $totals = ['processed' => 0, 'successful' => 0, 'failed' => 0];
            $allErrors = [];
            $allConflicts = [];
            
            foreach ($chunks as $chunkIndex => $chunk) {
                $result = $this->processChunk($chunk, $headers, $chunkIndex);
                
                // Actualizar totales
                $totals['processed'] += count($chunk);
                $totals['failed'] += count($result['errors']);
                $totals['successful'] = $totals['processed'] - $totals['failed'];
                
                // Acumular errores y conflictos
                $allErrors = array_merge($allErrors, $result['errors']);
                $allConflicts = array_merge($allConflicts, $result['conflicts']);
                
                // Actualizar progreso
                $this->csvImport->updateProgress(
                    $totals['processed'], 
                    $totals['successful'], 
                    $totals['failed'], 
                    $result['errors']
                );
                
                // Guardar conflictos
                if (!empty($allConflicts)) {
                    $this->csvImport->update(['conflict_resolution' => $allConflicts]);
                }
                
                usleep(100000); // Pausa de 0.1s entre chunks
            }
            
            $this->csvImport->markAsCompleted();
            
        } catch (\Throwable $e) {
            Log::error("ProcessUsersCsvImport failed: " . $e->getMessage());
            $this->csvImport->markAsFailed(["Error: " . $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Leer y validar el archivo CSV
     */
    private function readAndValidateCsv(): ?array
    {
        $filePath = "imports/{$this->csvImport->filename}";
        
        if (!Storage::exists($filePath)) {
            $this->csvImport->markAsFailed(["Archivo no encontrado: {$filePath}"]);
            return null;
        }
        
        $fileContent = Storage::get($filePath);
        $csvData = array_map('str_getcsv', explode("\n", trim($fileContent)));
        
        if (empty($csvData)) {
            $this->csvImport->markAsFailed(['El archivo CSV está vacío']);
            return null;
        }
        
        if (empty($this->csvImport->field_mappings)) {
            $this->csvImport->markAsFailed(['No se han definido mapeos de campos']);
            return null;
        }
        
        // Validar campos prohibidos
        $forbidden = ['password', 'roles', 'tenant_id', 'id'];
        foreach ($this->csvImport->field_mappings as $csvField => $modelField) {
            if (in_array($modelField, $forbidden)) {
                $this->csvImport->markAsFailed(["Campo prohibido: {$modelField}"]);
                return null;
            }
        }
        
        return $csvData;
    }

    /**
     * Procesar un chunk de filas
     */
    private function processChunk(array $chunk, array $headers, int $chunkIndex): array
    {
        $errors = [];
        $conflicts = [];
        $baseRow = ($chunkIndex * $this->csvImport->batch_size) + 2;
        
        foreach ($chunk as $index => $row) {
            $rowNumber = $baseRow + $index;
            
            try {
                // Combinar headers con valores
                $csvRow = array_combine($headers, $row);
                if (!$csvRow) {
                    $errors[] = "Fila {$rowNumber}: Error al procesar fila";
                    continue;
                }
                
                // Mapear campos según configuración
                $userData = $this->mapCsvToUserData($csvRow);
                
                // Limpiar datos (convertir strings vacíos a null)
                $userData = $this->cleanData($userData);
                
                // GUARDAR DATOS ORIGINALES DEL CSV ANTES DE TRANSFORMAR
                $originalCsvData = $userData;
                
                // Resolver ubicaciones si es necesario
                if ($this->hasLocationData($userData)) {
                    $userData = $this->locationResolver->resolveLocationIds($userData);
                }
                
                // Validar datos requeridos
                $validation = $this->validateUserData($userData, $rowNumber);
                if ($validation) {
                    $errors[] = $validation;
                    continue;
                }
                
                // Procesar el registro
                $result = $this->processUserRecord($userData, $rowNumber);
                
                if ($result['type'] === 'error') {
                    $errors[] = $result['message'];
                } elseif ($result['type'] === 'conflict') {
                    $conflicts[] = [
                        'id' => uniqid(),
                        'row' => $rowNumber,
                        'type' => $result['conflict_type'],
                        'description' => $result['message'],
                        'data' => $originalCsvData, // Datos originales para MOSTRAR
                        'data_with_ids' => $userData, // Datos con IDs para PROCESAR
                        'existing_user' => $result['existing_user'] ?? null,
                        'conflicts' => $result['conflicts'] ?? [], // GUARDAR qué campos cambiaron
                        'resolved' => false
                    ];
                }
                
            } catch (\Throwable $e) {
                $errors[] = "Fila {$rowNumber}: " . $e->getMessage();
                Log::error("Error procesando fila {$rowNumber}", ['exception' => $e]);
            }
        }
        
        return ['errors' => $errors, 'conflicts' => $conflicts];
    }

    /**
     * Mapear datos del CSV a campos del modelo User
     */
    private function mapCsvToUserData(array $csvRow): array
    {
        $userData = [];
        
        foreach ($this->csvImport->field_mappings as $csvField => $modelField) {
            if ($modelField && isset($csvRow[$csvField])) {
                $userData[$modelField] = $csvRow[$csvField];
            }
        }
        
        return $userData;
    }

    /**
     * Limpiar datos (convertir strings vacíos a null)
     */
    private function cleanData(array $data): array
    {
        $cleaned = [];
        
        foreach ($data as $field => $value) {
            if (is_string($value) && trim($value) === '') {
                $cleaned[$field] = null;
            } else {
                $cleaned[$field] = $value;
            }
        }
        
        return $cleaned;
    }

    /**
     * Verificar si hay datos de ubicación
     */
    private function hasLocationData(array $userData): bool
    {
        $locationFields = ['territorio_id', 'departamento_id', 'municipio_id', 'localidad_id'];
        
        foreach ($locationFields as $field) {
            if (!empty($userData[$field])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validar datos del usuario
     */
    private function validateUserData(array $userData, int $rowNumber): ?string
    {
        // Campos requeridos para crear
        if (empty($userData['email'])) {
            return "Fila {$rowNumber}: Email es requerido";
        }
        
        // Validar formato de email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            return "Fila {$rowNumber}: Email inválido ({$userData['email']})";
        }
        
        // Validar documento si está presente
        if (!empty($userData['documento_identidad']) && !is_numeric($userData['documento_identidad'])) {
            return "Fila {$rowNumber}: Documento debe ser numérico";
        }
        
        return null;
    }

    /**
     * Procesar un registro de usuario
     */
    private function processUserRecord(array $userData, int $rowNumber): array
    {
        // Buscar usuarios existentes
        $existingByEmail = null;
        $existingByDocument = null;
        
        if (!empty($userData['email'])) {
            $existingByEmail = User::where('email', $userData['email'])->first();
        }
        
        if (!empty($userData['documento_identidad'])) {
            $existingByDocument = User::where('documento_identidad', $userData['documento_identidad'])->first();
        }
        
        // Detectar conflictos de integridad
        if ($existingByEmail && $existingByDocument && $existingByEmail->id !== $existingByDocument->id) {
            return [
                'type' => 'conflict',
                'conflict_type' => 'email_document_mismatch',
                'message' => "Email y documento pertenecen a usuarios diferentes",
                'existing_user' => $existingByEmail->only(['id', 'name', 'email', 'documento_identidad'])
            ];
        }
        
        // Procesar según el modo configurado
        $mode = $this->csvImport->import_mode;
        
        switch ($mode) {
            case 'insert':
                return $this->handleInsert($userData, $existingByEmail, $existingByDocument, $rowNumber);
                
            case 'update':
                return $this->handleUpdate($userData, $existingByEmail, $existingByDocument, $rowNumber);
                
            case 'both':
                $existing = $existingByEmail ?: $existingByDocument;
                if ($existing) {
                    return $this->handleUpdate($userData, $existingByEmail, $existingByDocument, $rowNumber);
                } else {
                    return $this->handleInsert($userData, $existingByEmail, $existingByDocument, $rowNumber);
                }
                
            default:
                return ['type' => 'error', 'message' => "Modo inválido: {$mode}"];
        }
    }

    /**
     * Manejar inserción de nuevo usuario
     */
    private function handleInsert(array $userData, ?User $existingByEmail, ?User $existingByDocument, int $rowNumber): array
    {
        // Si ya existe, es un error en modo insert
        if ($existingByEmail || $existingByDocument) {
            return [
                'type' => 'error',
                'message' => "Fila {$rowNumber}: Usuario ya existe"
            ];
        }
        
        // Preparar datos para crear
        $createData = [];
        foreach ($userData as $field => $value) {
            // Solo incluir campos con valores no vacíos
            if ($value !== null && $value !== '') {
                $createData[$field] = $value;
            }
        }
        
        // Normalizar el nombre y email
        if (isset($createData['name'])) {
            $createData['name'] = Str::title(mb_strtolower(trim($createData['name'])));
        }
        if (isset($createData['email'])) {
            $createData['email'] = mb_strtolower(trim($createData['email']));
        }
        
        // Generar contraseña aleatoria segura
        // Formato: 12 caracteres aleatorios + 2 números + 1 símbolo
        $randomPassword = Str::random(12) . rand(10, 99) . '!';
        $createData['password'] = bcrypt($randomPassword);
        
        // Log de la contraseña generada (solo para desarrollo, remover en producción)
        Log::info("Usuario importado - Email: " . ($createData['email'] ?? 'N/A') . ", Contraseña generada: {$randomPassword}");
        
        // Campos por defecto
        $createData['activo'] = $createData['activo'] ?? true;
        
        // Crear usuario
        $user = User::create($createData);
        
        // Asignar rol por defecto
        $this->assignDefaultRole($user);
        
        // Asignar a votación si aplica
        $this->assignToVotacionIfApplicable($user);
        
        return ['type' => 'success', 'user' => $user];
    }

    /**
     * Manejar actualización de usuario existente
     */
    private function handleUpdate(array $userData, ?User $existingByEmail, ?User $existingByDocument, int $rowNumber): array
    {
        // Determinar qué usuario actualizar
        $userToUpdate = $existingByEmail ?: $existingByDocument;
        
        if (!$userToUpdate) {
            return [
                'type' => 'error',
                'message' => "Fila {$rowNumber}: Usuario no encontrado para actualizar"
            ];
        }
        
        // SOLO detectar conflictos para email/cédula cruzados
        $conflicts = [];
        
        // CONFLICTO 1: Encontramos por documento pero el email es diferente
        if ($existingByDocument && !$existingByEmail && !empty($userData['email'])) {
            if ($userToUpdate->email != $userData['email']) {
                $conflicts['email'] = [
                    'old' => $userToUpdate->email,
                    'new' => $userData['email'],
                    'reason' => 'La cédula existe con un email diferente'
                ];
            }
        }
        
        // CONFLICTO 2: Encontramos por email pero el documento es diferente
        if ($existingByEmail && !$existingByDocument && !empty($userData['documento_identidad'])) {
            if ($userToUpdate->documento_identidad != $userData['documento_identidad']) {
                $conflicts['documento_identidad'] = [
                    'old' => $userToUpdate->documento_identidad,
                    'new' => $userData['documento_identidad'],
                    'reason' => 'El email existe con una cédula diferente'
                ];
            }
        }
        
        // NO generar conflictos para otros campos - se actualizan directamente
        // Solo hay conflicto si email/cédula están cruzados
        
        // Si hay conflictos de email/cédula cruzados, reportarlo
        if (!empty($conflicts)) {
            Log::warning("CONFLICTOS DETECTADOS - Fila {$rowNumber}", [
                'user_id' => $userToUpdate->id,
                'conflicts' => $conflicts
            ]);
            
            return [
                'type' => 'conflict',
                'conflict_type' => 'field_changes',
                'message' => "Cambios detectados en campos críticos",
                'existing_user' => $userToUpdate->toArray(),
                'conflicts' => $conflicts
            ];
        }
        
        // Determinar qué campos actualizar
        $fieldsToUpdate = [];
        
        if (!empty($this->csvImport->update_fields)) {
            // Solo actualizar campos específicos seleccionados
            foreach ($this->csvImport->update_fields as $field) {
                if (array_key_exists($field, $userData)) {
                    $fieldsToUpdate[$field] = $userData[$field];
                }
            }
        } else {
            // Si NO hay update_fields, actualizar TODOS los campos del CSV
            $fieldsToUpdate = $userData;
        }
        
        // REGLA CRÍTICA: NO actualizar campos existentes con valores vacíos
        $updateData = [];
        foreach ($fieldsToUpdate as $field => $newValue) {
            // Nunca actualizar password desde CSV
            if ($field === 'password') {
                continue;
            }
            
            // Obtener valor actual del usuario
            $currentValue = $userToUpdate->$field;
            
            // SI el campo actual tiene valor Y el CSV viene vacío → NO ACTUALIZAR
            if (!empty($currentValue) && $this->isEmptyValue($newValue)) {
                Log::info("Preservando campo {$field} - tiene valor: {$currentValue}, CSV vacío");
                continue; // Preservar valor existente
            }
            
            // Aplicar normalización para nombre y email
            if ($field === 'name' && !$this->isEmptyValue($newValue)) {
                $newValue = Str::title(mb_strtolower(trim($newValue)));
            } elseif ($field === 'email' && !$this->isEmptyValue($newValue)) {
                $newValue = mb_strtolower(trim($newValue));
            }
            
            // Solo actualizar si hay un cambio real
            if ($currentValue != $newValue) {
                $updateData[$field] = $newValue;
            }
        }
        
        // Si hay cambios, aplicarlos
        if (!empty($updateData)) {
            Log::info("Actualizando usuario {$userToUpdate->id} - Fila {$rowNumber}", $updateData);
            $userToUpdate->update($updateData);
        }
        
        // Asignar a votación si aplica (incluso si no hubo cambios en los datos)
        $this->assignToVotacionIfApplicable($userToUpdate);
        
        if (!empty($updateData)) {
            return ['type' => 'success', 'user' => $userToUpdate];
        }
        
        return ['type' => 'info', 'message' => "Fila {$rowNumber}: Sin cambios", 'user' => $userToUpdate];
    }
    
    /**
     * Verificar si un valor está vacío
     */
    private function isEmptyValue($value): bool
    {
        return $value === null || 
               $value === '' || 
               (is_string($value) && trim($value) === '');
    }

    /**
     * Asignar rol por defecto
     */
    private function assignDefaultRole(User $user): void
    {
        try {
            $defaultRoleId = env('DEFAULT_USER_ROLE_ID', 4);
            
            DB::table('role_user')->insert([
                'user_id' => $user->id,
                'role_id' => $defaultRoleId,
                'assigned_at' => now(),
                'assigned_by' => null
            ]);
            
        } catch (\Throwable $e) {
            Log::error("Error asignando rol al usuario {$user->id}: " . $e->getMessage());
        }
    }
    
    /**
     * Asignar usuario a votación o asamblea si la importación está asociada
     */
    private function assignToVotacionIfApplicable(User $user): void
    {
        // Verificar si la importación tiene una votación asociada
        if ($this->csvImport->votacion_id) {
            try {
                // Verificar si el usuario ya está asignado a la votación
                $existingAssignment = DB::table('votacion_usuario')
                    ->where('votacion_id', $this->csvImport->votacion_id)
                    ->where('usuario_id', $user->id)
                    ->first();
                
                if (!$existingAssignment) {
                    // Obtener el tenant_id de la votación misma
                    $votacion = \App\Models\Votacion::find($this->csvImport->votacion_id);
                    if (!$votacion) {
                        Log::error("Votación {$this->csvImport->votacion_id} no encontrada");
                        return;
                    }
                    
                    // Asignar usuario a la votación
                    DB::table('votacion_usuario')->insert([
                        'votacion_id' => $this->csvImport->votacion_id,
                        'usuario_id' => $user->id,
                        'tenant_id' => $votacion->tenant_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    Log::info("Usuario {$user->id} asignado a votación {$this->csvImport->votacion_id}");
                } else {
                    Log::info("Usuario {$user->id} ya estaba asignado a votación {$this->csvImport->votacion_id}");
                }
                
            } catch (\Throwable $e) {
                Log::error("Error asignando usuario {$user->id} a votación {$this->csvImport->votacion_id}: " . $e->getMessage());
            }
        }
        
        // Verificar si la importación tiene una asamblea asociada
        if ($this->csvImport->asamblea_id) {
            try {
                // Verificar si el usuario ya está asignado a la asamblea
                $existingAssignment = DB::table('asamblea_usuario')
                    ->where('asamblea_id', $this->csvImport->asamblea_id)
                    ->where('usuario_id', $user->id)
                    ->first();
                
                if (!$existingAssignment) {
                    // Obtener el tenant_id de la asamblea
                    $asamblea = \App\Models\Asamblea::find($this->csvImport->asamblea_id);
                    if (!$asamblea) {
                        Log::error("Asamblea {$this->csvImport->asamblea_id} no encontrada");
                        return;
                    }
                    
                    // Asignar usuario a la asamblea
                    DB::table('asamblea_usuario')->insert([
                        'asamblea_id' => $this->csvImport->asamblea_id,
                        'usuario_id' => $user->id,
                        'tenant_id' => $asamblea->tenant_id,
                        'tipo_participacion' => 'asistente',
                        'asistio' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    Log::info("Usuario {$user->id} asignado a asamblea {$this->csvImport->asamblea_id}");
                } else {
                    Log::info("Usuario {$user->id} ya estaba asignado a asamblea {$this->csvImport->asamblea_id}");
                }
                
            } catch (\Throwable $e) {
                Log::error("Error asignando usuario {$user->id} a asamblea {$this->csvImport->asamblea_id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Manejar fallo del job
     */
    public function failed(?\Throwable $exception): void
    {
        $this->csvImport->markAsFailed([
            "Job falló: " . ($exception?->getMessage() ?? 'Error desconocido')
        ]);
    }
}