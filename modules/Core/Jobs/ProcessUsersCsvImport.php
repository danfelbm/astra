<?php

namespace Modules\Core\Jobs;

use Modules\Core\Models\CsvImport;
use Modules\Core\Models\User;
use Modules\Geografico\Services\LocationResolverService;
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

    public int $timeout = 1800; // 30 minutos para archivos grandes
    public int $tries = 2; // Reducir intentos para jobs largos
    protected LocationResolverService $locationResolver;
    protected int $updateFrequency = 5; // Actualizar progreso cada 5 chunks

    public function __construct(
        public CsvImport $csvImport
    ) {
        $this->locationResolver = app(LocationResolverService::class);
    }

    /**
     * Ejecutar el job principal - OPTIMIZADO PARA ARCHIVOS GRANDES
     */
    public function handle(): void
    {
        try {
            $this->csvImport->markAsProcessing();
            
            // Leer y validar archivo CSV (ahora usa streaming)
            $csvInfo = $this->readAndValidateCsv();
            if (!$csvInfo) {
                return;
            }
            
            // Extraer información
            $headers = $csvInfo['headers'];
            $fileObject = $csvInfo['file_object'];
            $totalRows = $csvInfo['total_rows'];
            
            Log::info("Iniciando procesamiento streaming", [
                'import_id' => $this->csvImport->id,
                'total_rows' => $totalRows,
                'batch_size' => $this->csvImport->batch_size
            ]);
            
            // Procesar archivo usando streaming chunks
            $this->processFileInChunks($fileObject, $headers, $totalRows);
            
            $this->csvImport->markAsCompleted();
            
        } catch (\Throwable $e) {
            Log::error("ProcessUsersCsvImport failed: " . $e->getMessage(), [
                'import_id' => $this->csvImport->id,
                'trace' => $e->getTraceAsString()
            ]);
            $this->csvImport->markAsFailed(["Error: " . $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Procesar archivo en chunks usando streaming - SIN CARGAR TODO A MEMORIA
     */
    private function processFileInChunks(\SplFileObject $file, array $headers, int $totalRows): void
    {
        $totals = ['processed' => 0, 'successful' => 0, 'failed' => 0];
        $allErrors = [];
        $allConflicts = [];
        $chunkIndex = 0;
        
        // Posicionarse después del header
        $file->rewind();
        $file->next();
        
        // Procesar archivo en chunks
        while (!$file->eof()) {
            // Leer chunk actual
            $chunk = $this->readChunk($file, $this->csvImport->batch_size);
            
            if (empty($chunk)) {
                break;
            }
            
            // Procesar chunk actual
            $result = $this->processChunk($chunk, $headers, $chunkIndex);
            
            // Actualizar totales
            $totals['processed'] += count($chunk);
            $totals['failed'] += count($result['errors']);
            $totals['successful'] = $totals['processed'] - $totals['failed'];
            
            // Acumular errores y conflictos
            $allErrors = array_merge($allErrors, $result['errors']);
            $allConflicts = array_merge($allConflicts, $result['conflicts']);
            
            // Actualizar progreso solo cada X chunks para evitar deadlocks
            if ($chunkIndex % $this->updateFrequency === 0 || empty($chunk)) {
                $this->updateProgressWithRetry(
                    $totals['processed'], 
                    $totals['successful'], 
                    $totals['failed'], 
                    $result['errors']
                );
            }
            
            // Guardar conflictos si los hay
            if (!empty($allConflicts)) {
                $this->csvImport->update(['conflict_resolution' => $allConflicts]);
            }
            
            // Log de progreso cada 10 chunks
            if ($chunkIndex % 10 === 0) {
                Log::info("Progreso CSV - Chunk {$chunkIndex}", [
                    'processed' => $totals['processed'],
                    'successful' => $totals['successful'], 
                    'failed' => $totals['failed'],
                    'percentage' => round(($totals['processed'] / $totalRows) * 100, 1)
                ]);
            }
            
            $chunkIndex++;
            
            // Pausa breve entre chunks para no saturar
            usleep(50000); // 0.05s
        }
        
        // Actualización final del progreso
        $this->updateProgressWithRetry(
            $totals['processed'], 
            $totals['successful'], 
            $totals['failed'], 
            $allErrors
        );
        
        Log::info("Procesamiento CSV completado", [
            'import_id' => $this->csvImport->id,
            'total_chunks' => $chunkIndex,
            'final_totals' => $totals
        ]);
    }
    
    /**
     * Leer un chunk de filas del archivo sin cargar todo a memoria
     */
    private function readChunk(\SplFileObject $file, int $batchSize): array
    {
        $chunk = [];
        $count = 0;
        
        while (!$file->eof() && $count < $batchSize) {
            $row = $file->current();
            
            // Solo incluir filas con datos válidos
            if (!empty($row) && is_array($row) && count(array_filter($row)) > 0) {
                $chunk[] = $row;
                $count++;
            }
            
            $file->next();
        }
        
        return $chunk;
    }

    /**
     * Leer y validar el archivo CSV - OPTIMIZADO PARA ARCHIVOS GRANDES
     */
    private function readAndValidateCsv(): ?array
    {
        $filePath = "imports/{$this->csvImport->filename}";
        $fullPath = Storage::path($filePath);
        
        if (!Storage::exists($filePath)) {
            $this->csvImport->markAsFailed(["Archivo no encontrado: {$filePath}"]);
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
        
        // Usar SplFileObject para lectura eficiente
        try {
            $file = new \SplFileObject($fullPath, 'r');
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
            
            // Leer solo el header para validación inicial
            $file->rewind();
            $headers = $file->current();
            
            if (empty($headers) || !is_array($headers)) {
                $this->csvImport->markAsFailed(['El archivo CSV está vacío o no tiene headers válidos']);
                return null;
            }
            
            // Contar filas de manera eficiente para archivos grandes
            $totalRows = $this->countCsvRows($file);
            
            if ($totalRows === 0) {
                $this->csvImport->markAsFailed(['El archivo CSV no tiene datos (solo headers)']);
                return null;
            }
            
            // Actualizar total_rows en base de datos
            $this->csvImport->update(['total_rows' => $totalRows]);
            
            Log::info("Archivo CSV validado - {$totalRows} filas a procesar", [
                'filename' => $this->csvImport->filename,
                'import_id' => $this->csvImport->id
            ]);
            
            // Para streaming, retornamos los headers y el objeto file
            // El contenido se leerá chunk por chunk en processChunks()
            return [
                'headers' => $headers,
                'file_object' => $file,
                'total_rows' => $totalRows
            ];
            
        } catch (\Exception $e) {
            $this->csvImport->markAsFailed(["Error leyendo CSV: " . $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Contar filas del CSV de manera eficiente
     */
    private function countCsvRows(\SplFileObject $file): int
    {
        $count = 0;
        $file->rewind();
        $file->next(); // Saltar header
        
        while (!$file->eof()) {
            $row = $file->current();
            if (!empty($row) && is_array($row) && count(array_filter($row)) > 0) {
                $count++;
            }
            $file->next();
        }
        
        return $count;
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
            
            // Asignar rol usando Spatie Laravel Permission
            $role = \App\Models\Role::find($defaultRoleId);
            if ($role) {
                $user->assignRole($role->name);
            } else {
                // Si no encuentra el rol por ID, intentar asignar el rol 'user' por defecto
                $user->assignRole('user');
            }
            
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
                    $asamblea = \Modules\Asamblea\Models\Asamblea::find($this->csvImport->asamblea_id);
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
     * Actualizar progreso con reintentos para manejar deadlocks
     */
    private function updateProgressWithRetry(
        int $processed, 
        int $successful, 
        int $failed, 
        array $errors,
        int $attempts = 3
    ): void {
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < $attempts) {
            try {
                // Usar transacción corta con timeout
                DB::transaction(function () use ($processed, $successful, $failed, $errors) {
                    $this->csvImport->updateProgress(
                        $processed, 
                        $successful, 
                        $failed, 
                        $errors
                    );
                }, 1); // Timeout de 1 intento de transacción
                
                return; // Éxito, salir del método
                
            } catch (\Illuminate\Database\QueryException $e) {
                $lastException = $e;
                
                // Si es un deadlock (código 1213), reintentar
                if ($e->getCode() === '40001' || strpos($e->getMessage(), '1213') !== false) {
                    $attempt++;
                    
                    Log::warning("Deadlock detectado al actualizar progreso, intento {$attempt}/{$attempts}", [
                        'import_id' => $this->csvImport->id,
                        'processed' => $processed
                    ]);
                    
                    // Esperar un tiempo aleatorio antes de reintentar (entre 100ms y 500ms)
                    usleep(rand(100000, 500000));
                    
                } else {
                    // Si no es un deadlock, lanzar la excepción
                    throw $e;
                }
            }
        }
        
        // Si llegamos aquí, agotamos los reintentos
        Log::error("No se pudo actualizar progreso después de {$attempts} intentos", [
            'import_id' => $this->csvImport->id,
            'error' => $lastException?->getMessage()
        ]);
        
        // NO lanzar excepción para no interrumpir el procesamiento
        // El progreso no se actualizó pero el procesamiento puede continuar
    }
    
    /**
     * Manejar fallo del job
     */
    public function failed(?\Throwable $exception): void
    {
        // Solo marcar como fallida si no es un deadlock temporal
        $isDeadlock = false;
        
        if ($exception instanceof \Illuminate\Database\QueryException) {
            $isDeadlock = $exception->getCode() === '40001' || 
                         strpos($exception->getMessage(), '1213') !== false;
        }
        
        if (!$isDeadlock) {
            $this->csvImport->markAsFailed([
                "Job falló: " . ($exception?->getMessage() ?? 'Error desconocido')
            ]);
        } else {
            Log::warning("Job terminó por deadlock pero el procesamiento puede haber continuado", [
                'import_id' => $this->csvImport->id
            ]);
        }
    }
}