<?php

namespace Modules\Core\Http\Middleware;

use Modules\Core\Helpers\RedisHelper;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ThrottleOTPRequests
{
    protected RateLimiter $limiter;
    
    /**
     * Umbrales de rate limiting según el tipo de request
     */
    protected array $limits = [
        'request-otp' => [
            'max_attempts' => 60,     // Máximo para evento masivo
            'decay_minutes' => 1,
            'suspicious_threshold' => 10, // Más de 10 en 1 minuto es sospechoso
            'block_duration' => 15,    // Bloqueo de 15 minutos si es sospechoso
        ],
        'verify-otp' => [
            'max_attempts' => 100,
            'decay_minutes' => 1,
            'suspicious_threshold' => 20, // Más de 20 intentos es sospechoso
            'block_duration' => 30,    // Bloqueo de 30 minutos
        ],
        'resend-otp' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
            'suspicious_threshold' => 5,
            'block_duration' => 60,    // Bloqueo de 1 hora
        ],
    ];
    
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'request-otp'): Response
    {
        $ip = $request->ip();
        $key = $this->resolveRequestSignature($request, $type);
        $config = $this->limits[$type] ?? $this->limits['request-otp'];
        
        // Verificar si la IP está bloqueada temporalmente
        if ($this->isBlocked($ip, $type)) {
            Log::warning('IP bloqueada por actividad sospechosa', [
                'ip' => $ip,
                'type' => $type,
                'user_agent' => $request->userAgent(),
            ]);
            
            return response()->json([
                'message' => 'Demasiados intentos. Por favor intente más tarde.',
                'retry_after' => $this->getBlockedTimeRemaining($ip, $type),
            ], 429);
        }
        
        // Verificar límite de rate
        $maxAttempts = $this->getMaxAttempts($ip, $config);
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $this->limiter->availableIn($key);
            
            // Detectar comportamiento sospechoso
            if ($this->isSuspiciousBehavior($ip, $type, $config)) {
                $this->blockIP($ip, $type, $config['block_duration']);
                
                Log::warning('Actividad sospechosa detectada - IP bloqueada', [
                    'ip' => $ip,
                    'type' => $type,
                    'attempts' => $this->getAttemptCount($ip, $type),
                    'block_duration' => $config['block_duration'],
                ]);
            }
            
            return response()->json([
                'message' => 'Demasiados intentos. Por favor espere antes de intentar nuevamente.',
                'retry_after' => $seconds,
            ], 429);
        }
        
        // Incrementar contador
        $this->limiter->hit($key, $config['decay_minutes'] * 60);
        $this->trackAttempt($ip, $type);
        
        $response = $next($request);
        
        // Agregar headers de rate limit
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $this->limiter->retriesLeft($key, $maxAttempts));
        
        return $response;
    }
    
    /**
     * Resolver la firma única del request
     */
    protected function resolveRequestSignature(Request $request, string $type): string
    {
        $ip = $request->ip();
        $userAgent = substr(md5($request->userAgent() ?? ''), 0, 8);
        
        // Incluir credencial en la firma para evitar ataques distribuidos
        $credential = $request->input('credential', '');
        $credentialHash = substr(md5($credential), 0, 8);
        
        return sprintf('otp_%s:%s:%s:%s', $type, $ip, $userAgent, $credentialHash);
    }
    
    /**
     * Obtener límite máximo de intentos (puede ser dinámico)
     */
    protected function getMaxAttempts(string $ip, array $config): int
    {
        // Si la IP tiene buen historial, permitir más intentos
        if ($this->hasGoodReputation($ip)) {
            return (int) ($config['max_attempts'] * 1.5);
        }
        
        // Si la IP tiene mal historial, reducir límites
        if ($this->hasBadReputation($ip)) {
            return (int) ($config['max_attempts'] * 0.5);
        }
        
        return $config['max_attempts'];
    }
    
    /**
     * Detectar comportamiento sospechoso
     */
    protected function isSuspiciousBehavior(string $ip, string $type, array $config): bool
    {
        $attemptCount = $this->getAttemptCount($ip, $type);
        
        // Si excede el umbral de intentos sospechosos
        if ($attemptCount > $config['suspicious_threshold']) {
            return true;
        }
        
        // Detectar patrones de ataque (múltiples tipos de request en poco tiempo)
        $recentTypes = $this->getRecentRequestTypes($ip);
        if (count($recentTypes) >= 3) {
            return true;
        }
        
        // Detectar intentos con múltiples credenciales diferentes
        $uniqueCredentials = $this->getUniqueCredentialsCount($ip);
        if ($uniqueCredentials > 5) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Rastrear intento
     */
    protected function trackAttempt(string $ip, string $type): void
    {
        $key = "otp_attempts:{$ip}:{$type}";
        $count = Cache::increment($key);
        
        // Establecer expiración si es el primer intento
        if ($count === 1) {
            Cache::put($key, $count, now()->addMinutes(5));
        }
        
        // Rastrear tipos de request
        $typesKey = "otp_types:{$ip}";
        $types = Cache::get($typesKey, []);
        $types[] = $type;
        Cache::put($typesKey, array_unique($types), now()->addMinutes(5));
    }
    
    /**
     * Obtener conteo de intentos
     */
    protected function getAttemptCount(string $ip, string $type): int
    {
        return (int) Cache::get("otp_attempts:{$ip}:{$type}", 0);
    }
    
    /**
     * Obtener tipos de request recientes
     */
    protected function getRecentRequestTypes(string $ip): array
    {
        return Cache::get("otp_types:{$ip}", []);
    }
    
    /**
     * Obtener cantidad de credenciales únicas intentadas
     */
    protected function getUniqueCredentialsCount(string $ip): int
    {
        return (int) Cache::get("otp_unique_credentials:{$ip}", 0);
    }
    
    /**
     * Verificar si IP tiene buena reputación
     */
    protected function hasGoodReputation(string $ip): bool
    {
        // Verificar si ha tenido intentos exitosos previos
        $successfulAttempts = Cache::get("otp_successful:{$ip}", 0);
        return $successfulAttempts > 3;
    }
    
    /**
     * Verificar si IP tiene mala reputación
     */
    protected function hasBadReputation(string $ip): bool
    {
        // Verificar si ha sido bloqueada recientemente
        $blockCount = Cache::get("otp_block_count:{$ip}", 0);
        return $blockCount > 0;
    }
    
    /**
     * Bloquear IP temporalmente
     */
    protected function blockIP(string $ip, string $type, int $minutes): void
    {
        $key = "otp_blocked:{$ip}:{$type}";
        Cache::put($key, true, now()->addMinutes($minutes));
        
        // Incrementar contador de bloqueos
        Cache::increment("otp_block_count:{$ip}");
    }
    
    /**
     * Verificar si IP está bloqueada
     */
    protected function isBlocked(string $ip, string $type): bool
    {
        return Cache::has("otp_blocked:{$ip}:{$type}");
    }
    
    /**
     * Obtener tiempo restante de bloqueo
     */
    protected function getBlockedTimeRemaining(string $ip, string $type): int
    {
        $key = "otp_blocked:{$ip}:{$type}";
        $ttl = Cache::getStore()->getRedis()->ttl(Cache::getPrefix() . $key);
        return max(0, $ttl);
    }
}