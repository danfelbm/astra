<?php

namespace App\Providers;

use App\Helpers\RedisHelper;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class OptimizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Evitar ejecutar durante package:discover para prevenir errores en composer install
        if ($this->shouldSkipOptimization()) {
            return;
        }
        
        // Configurar drivers dinámicamente según disponibilidad de Redis
        $this->configureDynamicDrivers();
        
        // Configurar optimizaciones de rendimiento
        $this->configurePerformanceOptimizations();
    }
    
    /**
     * Configurar drivers dinámicamente basado en disponibilidad de Redis
     */
    /**
     * Determinar si debemos saltar las optimizaciones
     */
    protected function shouldSkipOptimization(): bool
    {
        // Saltar durante composer install/update
        if (isset($_SERVER['argv']) && 
            (in_array('package:discover', $_SERVER['argv']) ||
             in_array('clear-compiled', $_SERVER['argv']))) {
            return true;
        }
        
        // Saltar durante testing
        if (app()->runningUnitTests()) {
            return true;
        }
        
        return false;
    }
    
    protected function configureDynamicDrivers(): void
    {
        try {
            // Verificar disponibilidad de Redis
            $redisAvailable = RedisHelper::isAvailable();
            
            if ($redisAvailable) {
                // Si Redis está disponible, usarlo para cache, sesiones y colas
                Config::set('cache.default', 'redis');
                Config::set('session.driver', 'redis');
                Config::set('queue.default', 'redis');
                
                // Log::info('Sistema configurado para usar Redis (óptimo para alta carga)');
            } else {
                // Fallback a database
                Config::set('cache.default', 'database');
                Config::set('session.driver', 'database');
                Config::set('queue.default', 'database');
                
                // Log::warning('Redis no disponible - usando database como fallback', [
                //     'recomendacion' => 'Instalar Redis para mejor rendimiento con alta carga'
                // ]);
            }
        } catch (\Exception $e) {
            // En caso de error, mantener configuración por defecto
            // Log::error('Error configurando drivers dinámicos: ' . $e->getMessage());
        }
    }
    
    /**
     * Configurar optimizaciones de rendimiento generales
     */
    protected function configurePerformanceOptimizations(): void
    {
        // Optimizar configuración de base de datos para alta carga
        if (config('database.default') === 'mysql') {
            // Aumentar el pool de conexiones MySQL
            Config::set('database.connections.mysql.options', array_merge(
                config('database.connections.mysql.options', []),
                [
                    \PDO::ATTR_PERSISTENT => true, // Conexiones persistentes
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // Queries con buffer
                    \PDO::ATTR_EMULATE_PREPARES => false, // Prepared statements reales
                ]
            ));
        }
        
        try {
            // Configurar cache de configuración si Redis está disponible
            if (RedisHelper::isAvailable()) {
                // Cache de rutas y configuración por 1 hora
                Config::set('cache.stores.config', [
                    'driver' => 'redis',
                    'connection' => 'cache',
                    'ttl' => 3600, // 1 hora
                ]);
            }
            
            // Optimizar rate limiting para usar cache disponible
            $cacheDriver = RedisHelper::getCacheDriver();
            Config::set('cache.limiter', $cacheDriver);
        } catch (\Exception $e) {
            // Ignorar errores de configuración de cache
            // Log::debug('No se pudo configurar cache optimizado: ' . $e->getMessage());
        }
        
        // Configurar garbage collection de sesiones
        if (config('session.driver') === 'database') {
            // Ejecutar limpieza de sesiones con menor frecuencia
            Config::set('session.lottery', [1, 1000]); // 0.1% de probabilidad
        }
    }
}