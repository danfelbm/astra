<?php

namespace Modules\Asamblea\Jobs;

use Modules\Asamblea\Models\Asamblea;
use Modules\Votaciones\Models\Votacion;
use Modules\Core\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncParticipantsToVotacionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de reintentos del job
     */
    public $tries = 3;

    /**
     * Tiempo máximo de ejecución (10 minutos)
     */
    public $timeout = 600;

    protected Asamblea $asamblea;
    protected Votacion $votacion;
    protected ?int $tenantId;
    protected string $jobId;
    protected string $cacheKey;

    /**
     * Create a new job instance.
     */
    public function __construct(Asamblea $asamblea, Votacion $votacion)
    {
        $this->asamblea = $asamblea;
        $this->votacion = $votacion;
        $this->tenantId = app(TenantService::class)->getCurrentTenant()?->id;
        $this->jobId = uniqid('sync_', true);
        $this->cacheKey = "sync_job_{$asamblea->id}_{$votacion->id}_{$this->jobId}";
        
        // Usar la cola default para procesamiento rápido
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Inicializar estado del job en cache
            $this->updateProgress(0, 'Iniciando sincronización...');

            // Obtener el total de participantes
            $totalParticipants = $this->asamblea->participantes()->count();
            
            if ($totalParticipants === 0) {
                $this->updateProgress(100, 'No hay participantes para sincronizar', 'completed');
                return;
            }

            Log::info('Iniciando sincronización de participantes', [
                'asamblea_id' => $this->asamblea->id,
                'votacion_id' => $this->votacion->id,
                'total_participantes' => $totalParticipants,
                'job_id' => $this->jobId
            ]);

            $processed = 0;
            $synced = 0;
            $errors = 0;
            $chunkSize = 500; // Procesar en chunks de 500

            // Procesar participantes en chunks
            $this->asamblea->participantes()->chunk($chunkSize, function ($participantes) use (&$processed, &$synced, &$errors, $totalParticipants) {
                
                $dataToInsert = [];
                
                foreach ($participantes as $participante) {
                    try {
                        // Verificar si el usuario ya está en la votación
                        $exists = DB::table('votacion_usuario')
                            ->where('votacion_id', $this->votacion->id)
                            ->where('usuario_id', $participante->id)
                            ->where('tenant_id', $this->tenantId)
                            ->exists();

                        if (!$exists) {
                            $dataToInsert[] = [
                                'votacion_id' => $this->votacion->id,
                                'usuario_id' => $participante->id,
                                'tenant_id' => $this->tenantId,
                                'origen_id' => $this->asamblea->id,
                                'model_type' => 'Modules\Asamblea\Models\Asamblea',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $synced++;
                        }
                        
                        $processed++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('Error sincronizando participante', [
                            'usuario_id' => $participante->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Insertar en batch si hay datos
                if (!empty($dataToInsert)) {
                    try {
                        DB::table('votacion_usuario')->insert($dataToInsert);
                    } catch (\Exception $e) {
                        $errors += count($dataToInsert);
                        $synced -= count($dataToInsert);
                        Log::error('Error insertando batch de participantes', [
                            'batch_size' => count($dataToInsert),
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Actualizar progreso
                $progress = round(($processed / $totalParticipants) * 100, 2);
                $message = "Procesados: {$processed}/{$totalParticipants} | Sincronizados: {$synced} | Errores: {$errors}";
                $this->updateProgress($progress, $message);
                
                // Pequeña pausa para no sobrecargar el sistema
                usleep(100000); // 100ms
            });

            // Actualizar estado final
            $finalMessage = "Sincronización completada. Total procesados: {$processed} | Sincronizados: {$synced} | Errores: {$errors}";
            $status = $errors > 0 ? 'completed_with_errors' : 'completed';
            $this->updateProgress(100, $finalMessage, $status);

            Log::info('Sincronización completada', [
                'asamblea_id' => $this->asamblea->id,
                'votacion_id' => $this->votacion->id,
                'procesados' => $processed,
                'sincronizados' => $synced,
                'errores' => $errors,
                'job_id' => $this->jobId
            ]);

        } catch (\Exception $e) {
            // Error general del job
            $this->updateProgress(0, 'Error en la sincronización: ' . $e->getMessage(), 'failed');
            
            Log::error('Error en job de sincronización', [
                'asamblea_id' => $this->asamblea->id,
                'votacion_id' => $this->votacion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'job_id' => $this->jobId
            ]);
            
            throw $e; // Relanzar para reintentos
        }
    }

    /**
     * Actualizar el progreso del job en cache
     */
    protected function updateProgress(float $progress, string $message, string $status = 'processing'): void
    {
        $data = [
            'job_id' => $this->jobId,
            'asamblea_id' => $this->asamblea->id,
            'votacion_id' => $this->votacion->id,
            'progress' => $progress,
            'message' => $message,
            'status' => $status,
            'updated_at' => now()->toISOString()
        ];

        // Guardar en cache por 1 hora
        Cache::put($this->cacheKey, $data, 3600);
        
        // También guardar una clave simple para búsqueda por job_id
        Cache::put("sync_job_{$this->jobId}", $data, 3600);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->updateProgress(0, 'Job falló después de todos los reintentos: ' . $exception->getMessage(), 'failed');
        
        Log::error('Job de sincronización falló definitivamente', [
            'asamblea_id' => $this->asamblea->id,
            'votacion_id' => $this->votacion->id,
            'error' => $exception->getMessage(),
            'job_id' => $this->jobId
        ]);

        // Aquí podrías enviar una notificación al administrador
    }

    /**
     * Get the job ID for tracking
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }
}