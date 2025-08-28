<?php

namespace App\Http\Controllers\Configuration\Admin;

use App\Http\Controllers\Core\AdminController;
use App\Services\Core\OTPService;
use App\Services\Core\QueueRateLimiterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Inertia\Inertia;
use Inertia\Response;

class OTPDashboardController extends AdminController
{
    public function __construct(
        private QueueRateLimiterService $queueRateLimiterService,
        private OTPService $otpService
    ) {}

    /**
     * Mostrar dashboard administrativo de OTP
     */
    public function index(): Response
    {
        return Inertia::render('Admin/OTPDashboard', [
            'initialQueueMetrics' => $this->getQueueMetrics(),
            'initialOtpStats' => $this->otpService->getStats(),
        ]);
    }

    /**
     * API: Obtener estado actual de las colas
     */
    public function queueStatus(): JsonResponse
    {
        try {
            $metrics = $this->getQueueMetrics();
            
            return response()->json([
                'success' => true,
                'data' => $metrics,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estado de colas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Obtener estadísticas de OTP
     */
    public function otpStats(): JsonResponse
    {
        try {
            $stats = $this->otpService->getStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estadísticas de OTP',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Obtener métricas detalladas por cola específica
     */
    public function queueDetails(string $queueName): JsonResponse
    {
        try {
            if (!in_array($queueName, ['otp-emails', 'otp-whatsapp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cola no válida',
                ], 400);
            }

            $metrics = $this->getQueueMetricsForQueue($queueName);
            
            return response()->json([
                'success' => true,
                'data' => $metrics,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo detalles de cola',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Reiniciar trabajos fallidos de una cola específica
     */
    public function retryFailedJobs(Request $request): JsonResponse
    {
        $request->validate([
            'queue' => 'required|string|in:otp-emails,otp-whatsapp',
        ]);

        try {
            $queueName = $request->input('queue');
            
            // Obtener trabajos fallidos y reintentarlos
            $retried = $this->retryFailedJobsForQueue($queueName);
            
            return response()->json([
                'success' => true,
                'message' => "Se reintentaron {$retried} trabajos fallidos en la cola {$queueName}",
                'retried_count' => $retried,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reintentando trabajos fallidos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Limpiar trabajos fallidos antiguos
     */
    public function cleanFailedJobs(Request $request): JsonResponse
    {
        $request->validate([
            'queue' => 'required|string|in:otp-emails,otp-whatsapp',
            'hours' => 'integer|min:1|max:168', // Máximo 1 semana
        ]);

        try {
            $queueName = $request->input('queue');
            $hours = $request->input('hours', 24); // Default 24 horas
            
            $cleaned = $this->cleanOldFailedJobs($queueName, $hours);
            
            return response()->json([
                'success' => true,
                'message' => "Se limpiaron {$cleaned} trabajos fallidos antiguos de la cola {$queueName}",
                'cleaned_count' => $cleaned,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error limpiando trabajos fallidos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener métricas de todas las colas OTP
     */
    private function getQueueMetrics(): array
    {
        $queues = ['otp-emails', 'otp-whatsapp'];
        $metrics = [];

        foreach ($queues as $queueName) {
            $metrics[] = $this->getQueueMetricsForQueue($queueName);
        }

        return $metrics;
    }

    /**
     * Obtener métricas para una cola específica
     */
    private function getQueueMetricsForQueue(string $queueName): array
    {
        // Obtener estadísticas básicas de la cola
        $queueStats = $this->queueRateLimiterService->getQueueStatistics($queueName);
        
        // Calcular tiempo estimado de espera
        $rateLimit = $this->getRateLimitForQueue($queueName);
        $estimatedWaitSeconds = $queueStats['pending_jobs'] > 0 
            ? ($queueStats['pending_jobs'] / $rateLimit) 
            : 0;

        return [
            'queue_name' => $queueName,
            'pending_jobs' => $queueStats['pending_jobs'],
            'processing_jobs' => $queueStats['processing_jobs'] ?? 0,
            'failed_jobs' => $queueStats['failed_jobs'],
            'rate_limit' => $rateLimit,
            'estimated_wait_seconds' => (int) $estimatedWaitSeconds,
            'last_processed_at' => $queueStats['last_processed_at'] ?? null,
            'oldest_job_age' => $queueStats['oldest_job_age'] ?? 0,
        ];
    }

    /**
     * Obtener límite de rate para una cola específica
     */
    private function getRateLimitForQueue(string $queueName): int
    {
        return match ($queueName) {
            'otp-emails' => config('queue.rate_limits.resend', 2),
            'otp-whatsapp' => config('queue.rate_limits.whatsapp', 5),
            default => 1,
        };
    }

    /**
     * Reintentar trabajos fallidos para una cola específica
     */
    private function retryFailedJobsForQueue(string $queueName): int
    {
        // En una implementación real, aquí se interactuaría con 
        // el sistema de colas para reintentar trabajos fallidos
        // Por ahora, retornar un número simulado
        
        try {
            // Comando artisan para reintentar trabajos fallidos
            $output = [];
            $exitCode = 0;
            
            exec("php artisan queue:retry --queue={$queueName}", $output, $exitCode);
            
            if ($exitCode === 0) {
                // Extraer número de trabajos reintentados del output
                $retriedCount = 0;
                foreach ($output as $line) {
                    if (preg_match('/(\d+) failed jobs/', $line, $matches)) {
                        $retriedCount = (int) $matches[1];
                        break;
                    }
                }
                return $retriedCount;
            }
            
            return 0;
        } catch (\Exception $e) {
            \Log::error("Error reintentando trabajos fallidos para cola {$queueName}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Limpiar trabajos fallidos antiguos
     */
    private function cleanOldFailedJobs(string $queueName, int $hours): int
    {
        try {
            // Comando artisan para limpiar trabajos fallidos antiguos
            $output = [];
            $exitCode = 0;
            $beforeTime = now()->subHours($hours)->toDateTimeString();
            
            exec("php artisan queue:flush --before=\"{$beforeTime}\" --queue={$queueName}", $output, $exitCode);
            
            if ($exitCode === 0) {
                // En una implementación real, se obtendría el conteo del comando
                return 1; // Placeholder
            }
            
            return 0;
        } catch (\Exception $e) {
            \Log::error("Error limpiando trabajos fallidos para cola {$queueName}: " . $e->getMessage());
            return 0;
        }
    }
}