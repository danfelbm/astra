<?php

namespace App\Jobs;

use App\Models\CsvImport;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProcessCsvImport implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout;
    public int $tries;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CsvImport $csvImport
    ) {
        $this->timeout = config('app.csv_import_timeout', 300);
        $this->tries = config('app.csv_import_max_retries', 3);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->csvImport->markAsProcessing();
            
            // Leer archivo CSV
            $filePath = "imports/{$this->csvImport->filename}";
            
            if (!Storage::exists($filePath)) {
                throw new Exception("Archivo CSV no encontrado: {$filePath}");
            }

            $fileContent = Storage::get($filePath);
            $csvData = array_map('str_getcsv', explode("\n", trim($fileContent)));
            
            // Validar que tenga contenido
            if (empty($csvData)) {
                throw new Exception('El archivo CSV está vacío.');
            }
            
            // Obtener headers (primera fila)
            $headers = array_shift($csvData);
            
            // Actualizar total de filas
            $this->csvImport->update(['total_rows' => count($csvData)]);
            
            // Validar headers requeridos
            $requiredHeaders = ['nombre', 'email', 'documento_identidad', 'territorio_id', 'departamento_id', 'municipio_id'];
            foreach ($requiredHeaders as $header) {
                if (!in_array($header, $headers)) {
                    throw new Exception("El archivo CSV debe contener la columna: {$header}");
                }
            }
            
            // Validación de seguridad: prevenir importación de campos administrativos
            $forbiddenHeaders = ['password', 'roles'];
            foreach ($forbiddenHeaders as $forbidden) {
                if (in_array($forbidden, $headers)) {
                    throw new Exception("Por razones de seguridad, no está permitido importar el campo: {$forbidden}");
                }
            }
            
            // Procesar en batches
            $chunks = array_chunk($csvData, $this->csvImport->batch_size);
            $totalProcessed = 0;
            $totalSuccessful = 0;
            $totalFailed = 0;
            $allErrors = [];
            
            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkErrors = $this->processChunk($chunk, $headers, $chunkIndex);
                
                // Contar resultados del chunk
                $chunkProcessed = count($chunk);
                $chunkFailed = count($chunkErrors);
                $chunkSuccessful = $chunkProcessed - $chunkFailed;
                
                // Actualizar totales
                $totalProcessed += $chunkProcessed;
                $totalSuccessful += $chunkSuccessful;
                $totalFailed += $chunkFailed;
                $allErrors = array_merge($allErrors, $chunkErrors);
                
                // Actualizar progreso en BD
                $this->csvImport->updateProgress($totalProcessed, $totalSuccessful, $totalFailed, $chunkErrors);
                
                // Pequeña pausa para evitar sobrecargar la BD
                usleep(100000); // 0.1 segundos
            }
            
            // Marcar como completado
            $this->csvImport->markAsCompleted();
            
        } catch (Exception $e) {
            $this->csvImport->markAsFailed(["Error general: " . $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Procesar un chunk de datos
     */
    private function processChunk(array $chunk, array $headers, int $chunkIndex): array
    {
        $errors = [];
        $baseRowNumber = ($chunkIndex * $this->csvImport->batch_size) + 2; // +2 por headers y índice base 1
        
        foreach ($chunk as $index => $row) {
            $rowNumber = $baseRowNumber + $index;
            
            try {
                // Combinar headers con valores
                $userData = array_combine($headers, $row);
                
                if ($userData === false) {
                    $errors[] = "Fila {$rowNumber}: Error al procesar datos de la fila";
                    continue;
                }
                
                // Validar datos requeridos
                if (empty($userData['nombre']) || empty($userData['email']) || empty($userData['documento_identidad'])) {
                    $errors[] = "Fila {$rowNumber}: nombre, email y documento_identidad son requeridos";
                    continue;
                }
                
                // Validar email
                if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Fila {$rowNumber}: email inválido ({$userData['email']})";
                    continue;
                }
                
                // Validar documento_identidad sea numérico
                if (!is_numeric($userData['documento_identidad'])) {
                    $errors[] = "Fila {$rowNumber}: documento_identidad debe ser numérico ({$userData['documento_identidad']})";
                    continue;
                }
                
                // Buscar usuarios existentes por email y documento
                $userByEmail = User::where('email', $userData['email'])->first();
                $userByDocument = User::where('documento_identidad', $userData['documento_identidad'])->first();
                
                // Lógica de validación según los casos
                if ($userByDocument && !$userByEmail) {
                    // Caso: documento existe pero email no
                    $errors[] = "Fila {$rowNumber}: documento {$userData['documento_identidad']} ya existe con email diferente ({$userByDocument->email})";
                    continue;
                } elseif ($userByEmail && !$userByDocument) {
                    // Caso: email existe pero documento no  
                    $errors[] = "Fila {$rowNumber}: email {$userData['email']} ya existe con documento diferente ({$userByEmail->documento_identidad})";
                    continue;
                } elseif ($userByEmail && $userByDocument) {
                    // Caso: ambos existen - validar que sean el mismo usuario
                    if ($userByEmail->id !== $userByDocument->id) {
                        $errors[] = "Fila {$rowNumber}: email y documento pertenecen a usuarios diferentes";
                        continue;
                    }
                    // Usuario existe con ambos datos correctos, solo asignarlo
                    $user = $userByEmail;
                } else {
                    // Caso: ninguno existe, crear nuevo usuario
                    $user = User::create([
                        'name' => $userData['nombre'],
                        'email' => $userData['email'],
                        'documento_identidad' => $userData['documento_identidad'],
                        'password' => bcrypt('temporal123'), // Password temporal
                        'territorio_id' => $userData['territorio_id'] ?: null,
                        'departamento_id' => $userData['departamento_id'] ?: null,
                        'municipio_id' => $userData['municipio_id'] ?: null,
                        'activo' => true
                    ]);
                }
                
                // Asignar a la votación si no está ya asignado
                if (!$this->csvImport->votacion->votantes()->where('usuario_id', $user->id)->exists()) {
                    // Usar el tenant_id del CsvImport (que tiene el trait HasTenant)
                    $tenantId = $this->csvImport->tenant_id;
                    $this->csvImport->votacion->votantes()->attach($user->id, ['tenant_id' => $tenantId]);
                }
                
            } catch (Exception $e) {
                $errors[] = "Fila {$rowNumber}: Error al procesar - " . $e->getMessage();
            }
        }
        
        return $errors;
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        $this->csvImport->markAsFailed([
            "Job falló: " . ($exception?->getMessage() ?? 'Error desconocido')
        ]);
    }
}
