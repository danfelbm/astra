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
        
        // Fallback a database si Redis no está disponible
        Log::warning('Redis no disponible, usando database cache como fallback');
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
        
        // Fallback a database si Redis no está disponible
        Log::warning('Redis no disponible, usando database sessions como fallback');
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
        
        // Fallback a database si Redis no está disponible
        Log::warning('Redis no disponible, usando database queue como fallback');
        return 'database';
    }
    
    /**
     * Resetear el cache de disponibilidad (útil para testing)
     */
    public static function resetCache(): void
    {
        self::$redisAvailable = null;
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