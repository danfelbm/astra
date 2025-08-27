<?php

namespace App\Services\Core;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class QueueRateLimiterService
{
    /**
     * Verificar si se puede procesar un job según el rate limit
     *
     * @param string $key Clave única del limitador
     * @param int $limit Límite de jobs
     * @param int $decay Período en segundos
     * @return array ['allowed' => bool, 'remaining' => int, 'retryAfter' => int]
     */
    public function throttle(string $key, int $limit, int $decay = 1): array
    {
        // En desarrollo sin Redis, retornar siempre permitido
        if (app()->environment('local') && !$this->isRedisAvailable()) {
            return [
                'allowed' => true,
                'remaining' => $limit,
                'retryAfter' => 0,
                'current' => 0,
                'limit' => $limit
            ];
        }
        
        $attempts = Redis::throttle($key)
            ->allow($limit)
            ->every($decay);
        
        $allowed = $attempts->attempt();
        
        // Obtener información adicional sobre el estado del throttle
        $current = $this->getCurrentAttempts($key);
        $remaining = max(0, $limit - $current);
        $retryAfter = $allowed ? 0 : $this->getRetryAfter($key, $decay);
        
        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'retryAfter' => $retryAfter,
            'current' => $current,
            'limit' => $limit
        ];
    }
    
    /**
     * Verificar si Redis está disponible
     *
     * @return bool
     */
    protected function isRedisAvailable(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener el número actual de intentos
     *
     * @param string $key
     * @return int
     */
    protected function getCurrentAttempts(string $key): int
    {
        if (app()->environment('local') && !$this->isRedisAvailable()) {
            return 0;
        }
        
        $redisKey = "throttle:{$key}:timer";
        $value = Redis::get($redisKey);
        
        return $value ? (int) $value : 0;
    }
    
    /**
     * Calcular tiempo de espera para reintentar
     *
     * @param string $key
     * @param int $decay
     * @return int
     */
    protected function getRetryAfter(string $key, int $decay): int
    {
        if (app()->environment('local') && !$this->isRedisAvailable()) {
            return 0;
        }
        
        $redisKey = "throttle:{$key}:timer";
        $ttl = Redis::ttl($redisKey);
        
        return max(0, $ttl);
    }
    
    /**
     * Obtener estadísticas de todas las colas OTP
     *
     * @return array
     */
    public function getQueueStats(): array
    {
        $stats = [];
        
        // En desarrollo sin Redis, retornar estadísticas simuladas
        if (app()->environment('local') && !$this->isRedisAvailable()) {
            return [
                'email' => [
                    'queue_name' => config('queue.otp_email_queue', 'otp-emails'),
                    'pending' => 0,
                    'processing' => 0,
                    'failed' => 0,
                    'total' => 0,
                    'rate_limit' => config('queue.rate_limits.resend', 2),
                    'throttle_status' => [
                        'allowed' => true,
                        'remaining' => 2,
                        'retryAfter' => 0,
                        'current' => 0,
                        'limit' => 2
                    ]
                ],
                'whatsapp' => [
                    'queue_name' => config('queue.otp_whatsapp_queue', 'otp-whatsapp'),
                    'pending' => 0,
                    'processing' => 0,
                    'failed' => 0,
                    'total' => 0,
                    'rate_limit' => config('queue.rate_limits.whatsapp', 5),
                    'throttle_status' => [
                        'allowed' => true,
                        'remaining' => 5,
                        'retryAfter' => 0,
                        'current' => 0,
                        'limit' => 5
                    ]
                ],
                'total' => [
                    'pending' => 0,
                    'processing' => 0,
                    'failed' => 0,
                ],
                'note' => 'Redis no disponible en desarrollo - rate limiting desactivado'
            ];
        }
        
        // Estadísticas de cola de emails OTP
        $emailQueueName = config('queue.otp_email_queue', 'otp-emails');
        $stats['email'] = $this->getQueueInfo($emailQueueName);
        $stats['email']['rate_limit'] = config('queue.rate_limits.resend', 2);
        $stats['email']['throttle_status'] = $this->throttle('otp_email', $stats['email']['rate_limit']);
        
        // Estadísticas de cola de WhatsApp OTP
        $whatsappQueueName = config('queue.otp_whatsapp_queue', 'otp-whatsapp');
        $stats['whatsapp'] = $this->getQueueInfo($whatsappQueueName);
        $stats['whatsapp']['rate_limit'] = config('queue.rate_limits.whatsapp', 5);
        $stats['whatsapp']['throttle_status'] = $this->throttle('otp_whatsapp', $stats['whatsapp']['rate_limit']);
        
        // Estadísticas generales
        $stats['total'] = [
            'pending' => $stats['email']['pending'] + $stats['whatsapp']['pending'],
            'processing' => $stats['email']['processing'] + $stats['whatsapp']['processing'],
            'failed' => $stats['email']['failed'] + $stats['whatsapp']['failed'],
        ];
        
        return $stats;
    }
    
    /**
     * Obtener información de una cola específica
     *
     * @param string $queue
     * @return array
     */
    protected function getQueueInfo(string $queue): array
    {
        $pending = DB::table('jobs')
            ->where('queue', $queue)
            ->whereNull('reserved_at')
            ->count();
        
        $processing = DB::table('jobs')
            ->where('queue', $queue)
            ->whereNotNull('reserved_at')
            ->count();
        
        $failed = DB::table('failed_jobs')
            ->where('queue', $queue)
            ->where('failed_at', '>=', now()->subDay())
            ->count();
        
        return [
            'queue_name' => $queue,
            'pending' => $pending,
            'processing' => $processing,
            'failed' => $failed,
            'total' => $pending + $processing,
        ];
    }
    
    /**
     * Estimar tiempo de espera para un nuevo job
     *
     * @param string $type 'email' o 'whatsapp'
     * @return array
     */
    public function estimateWaitTime(string $type): array
    {
        $queueName = $type === 'email' 
            ? config('queue.otp_email_queue', 'otp-emails')
            : config('queue.otp_whatsapp_queue', 'otp-whatsapp');
        
        $rateLimit = $type === 'email'
            ? config('queue.rate_limits.resend', 2)
            : config('queue.rate_limits.whatsapp', 5);
        
        // Obtener jobs pendientes
        $pendingJobs = DB::table('jobs')
            ->where('queue', $queueName)
            ->whereNull('reserved_at')
            ->count();
        
        // Calcular tiempo estimado
        $secondsPerJob = 1 / $rateLimit;
        $estimatedSeconds = (int) ($pendingJobs * $secondsPerJob);
        
        // Agregar buffer del 20% para variaciones
        $estimatedSeconds = (int) ($estimatedSeconds * 1.2);
        
        return [
            'type' => $type,
            'pending_jobs' => $pendingJobs,
            'rate_limit' => $rateLimit,
            'estimated_seconds' => $estimatedSeconds,
            'estimated_time' => $this->formatTime($estimatedSeconds),
            'position' => $pendingJobs + 1,
        ];
    }
    
    /**
     * Obtener posición en cola para un email/teléfono específico
     *
     * @param string $type 'email' o 'whatsapp'
     * @param string $identifier Email o teléfono
     * @return array|null
     */
    public function getQueuePosition(string $type, string $identifier): ?array
    {
        $queueName = $type === 'email' 
            ? config('queue.otp_email_queue', 'otp-emails')
            : config('queue.otp_whatsapp_queue', 'otp-whatsapp');
        
        // Buscar el job en la cola
        $jobs = DB::table('jobs')
            ->where('queue', $queueName)
            ->whereNull('reserved_at')
            ->orderBy('id')
            ->get();
        
        $position = 0;
        $found = false;
        
        foreach ($jobs as $job) {
            $position++;
            $payload = json_decode($job->payload, true);
            
            // Verificar si el job corresponde al identificador
            if ($this->jobMatchesIdentifier($payload, $type, $identifier)) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return null;
        }
        
        $rateLimit = $type === 'email'
            ? config('queue.rate_limits.resend', 2)
            : config('queue.rate_limits.whatsapp', 5);
        
        $secondsPerJob = 1 / $rateLimit;
        $estimatedSeconds = (int) (($position - 1) * $secondsPerJob);
        
        return [
            'type' => $type,
            'identifier' => $identifier,
            'position' => $position,
            'total_ahead' => $position - 1,
            'estimated_seconds' => $estimatedSeconds,
            'estimated_time' => $this->formatTime($estimatedSeconds),
        ];
    }
    
    /**
     * Verificar si un job corresponde a un identificador
     *
     * @param array $payload
     * @param string $type
     * @param string $identifier
     * @return bool
     */
    protected function jobMatchesIdentifier(array $payload, string $type, string $identifier): bool
    {
        if (!isset($payload['data']['command'])) {
            return false;
        }
        
        try {
            $command = unserialize($payload['data']['command']);
            
            if (!is_object($command)) {
                return false;
            }
            
            if ($type === 'email' && property_exists($command, 'email')) {
                return $command->email === $identifier;
            }
            
            if ($type === 'whatsapp' && property_exists($command, 'phone')) {
                return $command->phone === $identifier;
            }
        } catch (\Exception $e) {
            // Si no se puede deserializar (ej: clase movida), ignorar el job
            Log::warning('No se pudo deserializar job en cola: ' . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    /**
     * Formatear tiempo en formato legible
     *
     * @param int $seconds
     * @return string
     */
    protected function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} segundos";
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return $remainingSeconds > 0 
                ? "{$minutes} minutos y {$remainingSeconds} segundos"
                : "{$minutes} minutos";
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $remainingMinutes > 0
            ? "{$hours} horas y {$remainingMinutes} minutos"
            : "{$hours} horas";
    }
    
    /**
     * Registrar métricas de rate limiting
     *
     * @param string $type
     * @param bool $allowed
     * @param int $retryAfter
     * @return void
     */
    public function trackMetrics(string $type, bool $allowed, int $retryAfter = 0): void
    {
        $key = "otp_metrics:{$type}:" . date('Y-m-d:H');
        
        if ($allowed) {
            Cache::increment("{$key}:sent");
        } else {
            Cache::increment("{$key}:throttled");
            
            // Registrar el delay acumulado
            Cache::increment("{$key}:delay_seconds", $retryAfter);
        }
        
        // Establecer expiración de 48 horas para las métricas
        Cache::put($key, true, now()->addHours(48));
    }
    
    /**
     * Obtener métricas de las últimas 24 horas
     *
     * @return array
     */
    public function getMetrics(): array
    {
        $metrics = [
            'email' => [],
            'whatsapp' => [],
        ];
        
        // Obtener métricas de las últimas 24 horas
        for ($i = 0; $i < 24; $i++) {
            $hour = now()->subHours($i);
            $key = $hour->format('Y-m-d:H');
            
            foreach (['email', 'whatsapp'] as $type) {
                $sent = Cache::get("otp_metrics:{$type}:{$key}:sent", 0);
                $throttled = Cache::get("otp_metrics:{$type}:{$key}:throttled", 0);
                $delaySeconds = Cache::get("otp_metrics:{$type}:{$key}:delay_seconds", 0);
                
                $metrics[$type][] = [
                    'hour' => $hour->format('Y-m-d H:00'),
                    'sent' => $sent,
                    'throttled' => $throttled,
                    'total' => $sent + $throttled,
                    'delay_seconds' => $delaySeconds,
                    'success_rate' => $sent + $throttled > 0 
                        ? round(($sent / ($sent + $throttled)) * 100, 2)
                        : 0,
                ];
            }
        }
        
        // Invertir para tener orden cronológico
        $metrics['email'] = array_reverse($metrics['email']);
        $metrics['whatsapp'] = array_reverse($metrics['whatsapp']);
        
        return $metrics;
    }
}