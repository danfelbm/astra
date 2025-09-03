<?php

namespace Modules\Core\Jobs;

use Modules\Core\Models\OTP;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanExpiredOTPsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de intentos
     */
    public $tries = 3;

    /**
     * Tiempo de espera antes de reintentar (segundos)
     */
    public $backoff = 30;

    /**
     * Timeout del job (segundos)
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Usar transacción para asegurar consistencia
            DB::transaction(function () {
                // Contar OTPs a eliminar para logging
                $countExpired = OTP::where('expira_en', '<', Carbon::now())->count();
                $countUsed = OTP::where('usado', true)
                    ->where('updated_at', '<', Carbon::now()->subHours(24))
                    ->count();
                
                // Eliminar OTPs expirados en lotes para evitar bloqueos largos
                $deletedExpired = 0;
                while (OTP::where('expira_en', '<', Carbon::now())->limit(1000)->exists()) {
                    $deleted = OTP::where('expira_en', '<', Carbon::now())
                        ->limit(1000)
                        ->delete();
                    $deletedExpired += $deleted;
                    
                    // Pequeña pausa para no saturar la DB
                    if ($deleted > 0) {
                        usleep(100000); // 100ms
                    }
                }
                
                // Eliminar OTPs usados hace más de 24 horas (para mantener historial temporal)
                $deletedUsed = 0;
                while (OTP::where('usado', true)
                    ->where('updated_at', '<', Carbon::now()->subHours(24))
                    ->limit(1000)
                    ->exists()) {
                    $deleted = OTP::where('usado', true)
                        ->where('updated_at', '<', Carbon::now()->subHours(24))
                        ->limit(1000)
                        ->delete();
                    $deletedUsed += $deleted;
                    
                    // Pequeña pausa para no saturar la DB
                    if ($deleted > 0) {
                        usleep(100000); // 100ms
                    }
                }
                
                // Logging de resultados
                if ($deletedExpired > 0 || $deletedUsed > 0) {
                    Log::info('Limpieza de OTPs completada', [
                        'expirados_eliminados' => $deletedExpired,
                        'usados_eliminados' => $deletedUsed,
                        'total_eliminados' => $deletedExpired + $deletedUsed,
                        'timestamp' => Carbon::now()->toDateTimeString(),
                    ]);
                }
            });
            
            // Optimizar tabla después de eliminaciones masivas (opcional)
            // Solo hacer esto en horas de baja actividad
            $hour = Carbon::now()->hour;
            if ($hour >= 2 && $hour <= 5) { // Entre 2 AM y 5 AM
                DB::statement('OPTIMIZE TABLE otps');
                Log::info('Tabla OTPs optimizada');
            }
            
        } catch (\Exception $e) {
            Log::error('Error en limpieza de OTPs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-lanzar para que el job system maneje el retry
            throw $e;
        }
    }
    
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de limpieza de OTPs falló definitivamente', [
            'error' => $exception->getMessage(),
        ]);
        
        // Aquí podrías enviar una notificación al administrador
        // Notification::send($admins, new OTPCleanupFailedNotification($exception));
    }
}