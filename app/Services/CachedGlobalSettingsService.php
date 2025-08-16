<?php

namespace App\Services;

use App\Enums\LoginType;
use App\Helpers\RedisHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio optimizado de configuraciones globales con cache
 * Extiende GlobalSettingsService para mantener compatibilidad
 */
class CachedGlobalSettingsService extends GlobalSettingsService
{
    /**
     * Duración del cache en segundos
     */
    const CACHE_TTL = 3600; // 1 hora
    
    /**
     * Prefijo para las keys de cache
     */
    const CACHE_PREFIX = 'global_settings:';
    
    /**
     * Obtener configuración de autenticación con cache
     */
    public static function getAuthConfig(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'auth_config';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            Log::info('Generando cache de configuración de autenticación');
            return parent::getAuthConfig();
        });
    }
    
    /**
     * Obtener tipo de login con cache
     */
    public static function getLoginType(): LoginType
    {
        $cacheKey = self::CACHE_PREFIX . 'login_type';
        
        $cached = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $type = parent::getLoginType();
            return $type->value; // Guardar como string en cache
        });
        
        return LoginType::from($cached);
    }
    
    /**
     * Obtener canal OTP con cache
     */
    public static function getOTPChannel(): string
    {
        $cacheKey = self::CACHE_PREFIX . 'otp_channel';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return parent::getOTPChannel();
        });
    }
    
    /**
     * Obtener todas las configuraciones con cache
     */
    public static function getAllSettings(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'all_settings';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            Log::info('Generando cache de todas las configuraciones');
            return parent::getAllSettings();
        });
    }
    
    /**
     * Limpiar todo el cache de configuraciones
     */
    public static function clearCache(): void
    {
        $keys = [
            'auth_config',
            'login_type',
            'otp_channel',
            'all_settings',
        ];
        
        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
        
        Log::info('Cache de configuraciones globales limpiado');
    }
    
    /**
     * Actualizar configuración y limpiar cache
     */
    public static function updateSetting(string $key, $value): void
    {
        // Actualizar en base de datos o archivo de configuración
        parent::updateSetting($key, $value);
        
        // Limpiar cache para forzar recarga
        self::clearCache();
        
        Log::info('Configuración actualizada y cache limpiado', [
            'key' => $key,
            'value' => $value
        ]);
    }
    
    /**
     * Pre-calentar el cache (útil después de deploy)
     */
    public static function warmCache(): void
    {
        Log::info('Pre-calentando cache de configuraciones...');
        
        // Forzar carga de todas las configuraciones en cache
        self::getAuthConfig();
        self::getLoginType();
        self::getOTPChannel();
        self::getAllSettings();
        
        Log::info('Cache de configuraciones pre-calentado exitosamente');
    }
    
    /**
     * Obtener estadísticas del cache
     */
    public static function getCacheStats(): array
    {
        $keys = [
            'auth_config',
            'login_type',
            'otp_channel',
            'all_settings',
        ];
        
        $stats = [
            'cached_items' => 0,
            'cache_driver' => config('cache.default'),
            'redis_available' => RedisHelper::isAvailable(),
            'items' => []
        ];
        
        foreach ($keys as $key) {
            $fullKey = self::CACHE_PREFIX . $key;
            if (Cache::has($fullKey)) {
                $stats['cached_items']++;
                $stats['items'][$key] = [
                    'cached' => true,
                    'ttl' => Cache::getStore()->getRedis()->ttl(Cache::getPrefix() . $fullKey) ?? 'N/A'
                ];
            } else {
                $stats['items'][$key] = [
                    'cached' => false,
                    'ttl' => 0
                ];
            }
        }
        
        return $stats;
    }
}