<?php

namespace Modules\Core\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisHelper
{
    /**
     * Cache estático para evitar múltiples checks
     */
    private static ?bool $redisAvailable = null;

    /**
     * Flag para loguear el warning solo una vez por request
     */
    private static bool $fallbackWarningLogged = false;
    
    /**
     * Verificar si Redis está disponible y funcionando
     */
    public static function isAvailable(): bool
    {
        // Usar cache estático para evitar múltiples checks en el mismo request
        if (self::$redisAvailable !== null) {
            return self::$redisAvailable;
        }
        
        try {
            // Primero verificar si la extensión Redis está instalada
            if (!extension_loaded('redis') && !class_exists('\Predis\Client')) {
                self::$redisAvailable = false;
                return false;
            }
            
            // SIEMPRE verificar conexión real, independientemente del entorno
            $redis = Redis::connection();
            $redis->ping();
            self::$redisAvailable = true;
            return true;
        } catch (\Exception $e) {
            self::$redisAvailable = false;
            return false;
        }
    }
    
    /**
     * Obtener el driver de cache óptimo según disponibilidad
     */
    public static function getCacheDriver(): string
    {
        if (self::isAvailable()) {
            return 'redis';
        }

        self::logFallbackWarning();
        return 'database';
    }

    /**
     * Obtener el driver de sesión óptimo según disponibilidad
     */
    public static function getSessionDriver(): string
    {
        if (self::isAvailable()) {
            return 'redis';
        }

        self::logFallbackWarning();
        return 'database';
    }

    /**
     * Obtener el driver de cola óptimo según disponibilidad
     */
    public static function getQueueDriver(): string
    {
        if (self::isAvailable()) {
            return 'redis';
        }

        self::logFallbackWarning();
        return 'database';
    }

    /**
     * Loguear warning de fallback solo una vez (persiste entre requests por 1 hora)
     */
    private static function logFallbackWarning(): void
    {
        if (self::$fallbackWarningLogged) {
            return;
        }

        $lockFile = storage_path('framework/cache/redis_fallback_warning.lock');
        $ttlSeconds = 3600; // 1 hora

        // Verificar si el archivo existe y no ha expirado
        if (file_exists($lockFile)) {
            $fileTime = filemtime($lockFile);
            if ($fileTime && (time() - $fileTime) < $ttlSeconds) {
                self::$fallbackWarningLogged = true;
                return;
            }
        }

        // Loguear y crear/actualizar el archivo de lock
        Log::warning('Redis no disponible, usando database como fallback para cache/sessions/queue');
        @touch($lockFile);
        self::$fallbackWarningLogged = true;
    }
    
    /**
     * Resetear el cache de disponibilidad (útil para testing)
     */
    public static function resetCache(): void
    {
        self::$redisAvailable = null;
        self::$fallbackWarningLogged = false;

        // Eliminar archivo de lock para permitir nuevo warning
        $lockFile = storage_path('framework/cache/redis_fallback_warning.lock');
        if (file_exists($lockFile)) {
            @unlink($lockFile);
        }
    }
    
    /**
     * Obtener estadísticas de Redis si está disponible
     */
    public static function getStats(): ?array
    {
        if (!self::isAvailable()) {
            return null;
        }
        
        try {
            $redis = Redis::connection();
            $info = $redis->info();
            
            return [
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_connections' => $info['total_connections_received'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'uptime_days' => isset($info['uptime_in_seconds']) 
                    ? round($info['uptime_in_seconds'] / 86400, 2) 
                    : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de Redis: ' . $e->getMessage());
            return null;
        }
    }
}