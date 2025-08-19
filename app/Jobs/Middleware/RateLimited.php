<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RateLimited
{
    /**
     * Clave para el rate limiter
     */
    protected string $key;
    
    /**
     * Número máximo de trabajos permitidos por período
     */
    protected int $maxAttempts;
    
    /**
     * Período de tiempo en segundos
     */
    protected int $decaySeconds;
    
    /**
     * Prefijo para identificar el servicio
     */
    protected string $prefix;
    
    /**
     * Constructor del middleware de rate limiting
     *
     * @param string $key Clave única para este rate limiter
     * @param int $maxAttempts Número máximo de jobs por período
     * @param int $decaySeconds Período en segundos (default 1)
     * @param string $prefix Prefijo para identificar el servicio
     */
    public function __construct(string $key, int $maxAttempts = 2, int $decaySeconds = 1, string $prefix = 'rate_limit')
    {
        $this->key = $key;
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
        $this->prefix = $prefix;
    }
    
    /**
     * Procesar el job con rate limiting
     *
     * @param mixed $job
     * @param callable $next
     * @return mixed
     */
    public function handle($job, Closure $next)
    {
        $rateLimitKey = $this->getRateLimitKey();
        
        // En desarrollo, si Redis no está disponible, simplemente ejecutar el job
        if (app()->environment('local') && !$this->isRedisAvailable()) {
            Log::info("Redis no disponible en local, ejecutando job sin rate limiting", [
                'job' => get_class($job),
                'key' => $this->key,
            ]);
            return $next($job);
        }
        
        // Usar Redis throttle para control de rate limiting
        return Redis::throttle($rateLimitKey)
            ->allow($this->maxAttempts)
            ->every($this->decaySeconds)
            ->then(function () use ($job, $next) {
                // Registrar que el job está siendo procesado
                Log::info("Job procesado dentro del límite de rate", [
                    'job' => get_class($job),
                    'key' => $this->key,
                    'limit' => "{$this->maxAttempts}/{$this->decaySeconds}s"
                ]);
                
                // Ejecutar el job
                return $next($job);
            }, function () use ($job) {
                // Rate limit alcanzado, reencolar con delay
                $delaySeconds = $this->calculateDelay($job);
                
                Log::warning("Rate limit alcanzado, reencolando job", [
                    'job' => get_class($job),
                    'key' => $this->key,
                    'delay' => $delaySeconds,
                    'limit' => "{$this->maxAttempts}/{$this->decaySeconds}s"
                ]);
                
                // Reencolar el job con delay
                $job->release($delaySeconds);
            });
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
     * Obtener la clave completa para el rate limiter
     *
     * @return string
     */
    protected function getRateLimitKey(): string
    {
        return "{$this->prefix}:{$this->key}";
    }
    
    /**
     * Calcular el delay para reencolar el job
     *
     * @param mixed $job
     * @return int
     */
    protected function calculateDelay($job): int
    {
        // Obtener el número de intentos del job
        $attempts = method_exists($job, 'attempts') ? $job->attempts() : 1;
        
        // Implementar backoff exponencial con límite
        $baseDelay = $this->decaySeconds;
        $maxDelay = 60; // Máximo 60 segundos
        
        // Calcular delay con backoff exponencial
        $delay = min($baseDelay * pow(2, $attempts - 1), $maxDelay);
        
        // Agregar un poco de jitter para evitar thundering herd
        $jitter = rand(0, 1000) / 1000; // 0-1 segundo de jitter
        
        return (int) ($delay + $jitter);
    }
    
    /**
     * Factory method para crear middleware de email
     *
     * @return static
     */
    public static function forEmail(): static
    {
        $limit = config('queue.rate_limits.resend', 2);
        return new static('otp_email', $limit, 1, 'resend');
    }
    
    /**
     * Factory method para crear middleware de WhatsApp
     *
     * @return static
     */
    public static function forWhatsApp(): static
    {
        $limit = config('queue.rate_limits.whatsapp', 5);
        return new static('otp_whatsapp', $limit, 1, 'whatsapp');
    }
    
    /**
     * Factory method para crear middleware personalizado
     *
     * @param string $service
     * @param int $maxAttempts
     * @param int $decaySeconds
     * @return static
     */
    public static function for(string $service, int $maxAttempts, int $decaySeconds = 1): static
    {
        return new static($service, $maxAttempts, $decaySeconds, $service);
    }
}