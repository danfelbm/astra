<?php

namespace Modules\Configuration\Console\Commands;

use Modules\Core\Helpers\RedisHelper;
use Modules\Core\Models\OTP;
use Modules\Core\Services\CachedGlobalSettingsService;
use Modules\Core\Services\OTPService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class MonitorOTPPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:monitor 
                            {--live : Monitoreo en tiempo real}
                            {--interval=5 : Intervalo de actualización en segundos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorear el rendimiento del sistema OTP y mostrar métricas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $live = $this->option('live');
        $interval = (int) $this->option('interval');
        
        if ($live) {
            $this->monitorLive($interval);
        } else {
            $this->showMetrics();
        }
    }
    
    /**
     * Mostrar métricas una sola vez
     */
    protected function showMetrics(): void
    {
        $this->newLine();
        $this->info('🔍 MONITOREO DE RENDIMIENTO - SISTEMA OTP');
        $this->info('==========================================');
        $this->newLine();
        
        // Estado de servicios
        $this->showServicesStatus();
        $this->newLine();
        
        // Métricas de OTP
        $this->showOTPMetrics();
        $this->newLine();
        
        // Métricas de cola
        $this->showQueueMetrics();
        $this->newLine();
        
        // Métricas de base de datos
        $this->showDatabaseMetrics();
        $this->newLine();
        
        // Métricas de cache
        $this->showCacheMetrics();
        $this->newLine();
        
        // Recomendaciones
        $this->showRecommendations();
    }
    
    /**
     * Monitoreo en tiempo real
     */
    protected function monitorLive(int $interval): void
    {
        $this->info('🔴 MONITOREO EN VIVO - Presiona Ctrl+C para salir');
        $this->newLine();
        
        while (true) {
            // Limpiar pantalla
            $this->clearScreen();
            
            // Mostrar timestamp
            $this->info('📊 ' . Carbon::now()->format('Y-m-d H:i:s'));
            $this->line(str_repeat('-', 60));
            
            // Métricas rápidas
            $this->showQuickMetrics();
            
            // Esperar intervalo
            sleep($interval);
        }
    }
    
    /**
     * Mostrar estado de servicios
     */
    protected function showServicesStatus(): void
    {
        $this->comment('📡 ESTADO DE SERVICIOS');
        $this->line(str_repeat('-', 40));
        
        $services = [
            'Redis' => RedisHelper::isAvailable() ? '✅ Activo' : '❌ Inactivo',
            'Queue Workers' => $this->getQueueWorkersStatus(),
            'Cache Driver' => config('cache.default'),
            'Session Driver' => config('session.driver'),
            'Queue Driver' => config('queue.default'),
            'OTP Envío' => config('services.otp.send_immediately') ? '⚠️ Síncrono' : '✅ Asíncrono',
        ];
        
        foreach ($services as $service => $status) {
            $this->line(sprintf('%-20s: %s', $service, $status));
        }
    }
    
    /**
     * Mostrar métricas de OTP
     */
    protected function showOTPMetrics(): void
    {
        $this->comment('🔐 MÉTRICAS DE OTP');
        $this->line(str_repeat('-', 40));
        
        // Estadísticas de OTPs
        $stats = (new OTPService())->getStats();
        
        $this->line(sprintf('OTPs Activos       : %d', $stats['total_activos']));
        $this->line(sprintf('OTPs Expirados     : %d', $stats['total_expirados']));
        $this->line(sprintf('OTPs Usados        : %d', $stats['total_usados']));
        
        // OTPs por canal
        $this->line('');
        $this->line('Por Canal:');
        foreach ($stats['por_canal'] as $canal => $count) {
            $this->line(sprintf('  - %-10s     : %d', ucfirst($canal), $count));
        }
        
        // Tasa de éxito (últimas 24 horas)
        $successRate = $this->calculateSuccessRate();
        $this->line('');
        $this->line(sprintf('Tasa de Éxito (24h): %.1f%%', $successRate));
        
        // Tiempo promedio de verificación
        $avgTime = $this->calculateAverageVerificationTime();
        $this->line(sprintf('Tiempo Promedio    : %.1f segundos', $avgTime));
    }
    
    /**
     * Mostrar métricas de cola
     */
    protected function showQueueMetrics(): void
    {
        $this->comment('📬 MÉTRICAS DE COLA');
        $this->line(str_repeat('-', 40));
        
        try {
            $queueSize = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            $processingJobs = DB::table('jobs')->whereNotNull('reserved_at')->count();
            
            $this->line(sprintf('Jobs en Cola       : %d', $queueSize));
            $this->line(sprintf('Jobs Procesando    : %d', $processingJobs));
            $this->line(sprintf('Jobs Fallidos      : %d', $failedJobs));
            
            if ($queueSize > 100) {
                $this->warn('⚠️ Cola con muchos jobs pendientes!');
            }
            
            if ($failedJobs > 0) {
                $this->warn('⚠️ Hay jobs fallidos que requieren atención');
            }
        } catch (\Exception $e) {
            $this->error('No se pudieron obtener métricas de cola');
        }
    }
    
    /**
     * Mostrar métricas de base de datos
     */
    protected function showDatabaseMetrics(): void
    {
        $this->comment('🗄️ MÉTRICAS DE BASE DE DATOS');
        $this->line(str_repeat('-', 40));
        
        try {
            // Conexiones activas
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
            $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'")[0]->Value ?? 0;
            
            $this->line(sprintf('Conexiones Activas : %d / %d', $connections, $maxConnections));
            
            // Tamaño de tablas relevantes
            $tables = ['otps', 'users', 'sessions', 'cache', 'jobs'];
            $this->line('');
            $this->line('Tamaño de Tablas:');
            
            foreach ($tables as $table) {
                $size = $this->getTableSize($table);
                $this->line(sprintf('  - %-12s   : %s', $table, $this->formatBytes($size)));
            }
            
            // Query cache (si está habilitado)
            $queryCacheHitRate = $this->getQueryCacheHitRate();
            if ($queryCacheHitRate !== null) {
                $this->line('');
                $this->line(sprintf('Query Cache Hit    : %.1f%%', $queryCacheHitRate));
            }
        } catch (\Exception $e) {
            $this->error('No se pudieron obtener métricas de base de datos');
        }
    }
    
    /**
     * Mostrar métricas de cache
     */
    protected function showCacheMetrics(): void
    {
        $this->comment('💾 MÉTRICAS DE CACHE');
        $this->line(str_repeat('-', 40));
        
        // Estado del cache de configuraciones
        $cacheStats = CachedGlobalSettingsService::getCacheStats();
        
        $this->line(sprintf('Driver de Cache    : %s', $cacheStats['cache_driver']));
        $this->line(sprintf('Items en Cache     : %d', $cacheStats['cached_items']));
        
        if ($cacheStats['redis_available']) {
            $redisStats = RedisHelper::getStats();
            if ($redisStats) {
                $this->line('');
                $this->line('Redis Stats:');
                $this->line(sprintf('  - Memoria Usada  : %s', $redisStats['used_memory']));
                $this->line(sprintf('  - Clientes       : %d', $redisStats['connected_clients']));
                $this->line(sprintf('  - Hit Rate       : %.1f%%', $this->calculateRedisHitRate($redisStats)));
            }
        }
    }
    
    /**
     * Mostrar recomendaciones
     */
    protected function showRecommendations(): void
    {
        $this->comment('💡 RECOMENDACIONES');
        $this->line(str_repeat('-', 40));
        
        $recommendations = [];
        
        // Verificar Redis
        if (!RedisHelper::isAvailable()) {
            $recommendations[] = '🔴 CRÍTICO: Instalar Redis para mejor rendimiento';
        }
        
        // Verificar envío asíncrono
        if (config('services.otp.send_immediately')) {
            $recommendations[] = '🔴 CRÍTICO: Cambiar OTP_SEND_IMMEDIATELY=false';
        }
        
        // Verificar workers
        $workers = $this->countActiveWorkers();
        if ($workers < 3) {
            $recommendations[] = '⚠️ IMPORTANTE: Iniciar más workers (mínimo 3-5)';
        }
        
        // Verificar índices
        $missingIndexes = $this->checkMissingIndexes();
        if (!empty($missingIndexes)) {
            $recommendations[] = '⚠️ IMPORTANTE: Ejecutar migraciones para índices';
        }
        
        // Verificar cola
        $queueSize = DB::table('jobs')->count();
        if ($queueSize > 100) {
            $recommendations[] = '⚠️ ADVERTENCIA: Cola saturada, agregar más workers';
        }
        
        if (empty($recommendations)) {
            $this->info('✅ Sistema optimizado correctamente!');
        } else {
            foreach ($recommendations as $rec) {
                $this->line($rec);
            }
        }
    }
    
    /**
     * Métricas rápidas para modo live
     */
    protected function showQuickMetrics(): void
    {
        $stats = (new OTPService())->getStats();
        $queueSize = DB::table('jobs')->count();
        $workers = $this->countActiveWorkers();
        
        $this->table(
            ['Métrica', 'Valor', 'Estado'],
            [
                ['OTPs Activos', $stats['total_activos'], $stats['total_activos'] < 100 ? '✅' : '⚠️'],
                ['Cola de Jobs', $queueSize, $queueSize < 50 ? '✅' : '⚠️'],
                ['Workers Activos', $workers, $workers >= 3 ? '✅' : '❌'],
                ['Redis', RedisHelper::isAvailable() ? 'Activo' : 'Inactivo', RedisHelper::isAvailable() ? '✅' : '❌'],
            ]
        );
    }
    
    // Métodos auxiliares
    
    protected function getQueueWorkersStatus(): string
    {
        $count = $this->countActiveWorkers();
        if ($count === 0) {
            return '❌ Sin workers';
        } elseif ($count < 3) {
            return "⚠️ {$count} workers (insuficiente)";
        } else {
            return "✅ {$count} workers activos";
        }
    }
    
    protected function countActiveWorkers(): int
    {
        exec("ps aux | grep 'artisan queue:work' | grep -v grep | wc -l", $output);
        return (int) ($output[0] ?? 0);
    }
    
    protected function calculateSuccessRate(): float
    {
        $total = OTP::where('created_at', '>=', Carbon::now()->subHours(24))->count();
        $used = OTP::where('created_at', '>=', Carbon::now()->subHours(24))
            ->where('usado', true)
            ->count();
        
        return $total > 0 ? ($used / $total) * 100 : 0;
    }
    
    protected function calculateAverageVerificationTime(): float
    {
        $avg = OTP::where('usado', true)
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_time')
            ->first();
        
        return $avg->avg_time ?? 0;
    }
    
    protected function getTableSize(string $table): int
    {
        $result = DB::select("
            SELECT data_length + index_length as size 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = ?
        ", [$table]);
        
        return $result[0]->size ?? 0;
    }
    
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    protected function getQueryCacheHitRate(): ?float
    {
        try {
            $stats = DB::select("SHOW STATUS LIKE 'Qcache_%'");
            $hits = 0;
            $inserts = 0;
            
            foreach ($stats as $stat) {
                if ($stat->Variable_name === 'Qcache_hits') {
                    $hits = (int) $stat->Value;
                } elseif ($stat->Variable_name === 'Qcache_inserts') {
                    $inserts = (int) $stat->Value;
                }
            }
            
            $total = $hits + $inserts;
            return $total > 0 ? ($hits / $total) * 100 : 0;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    protected function calculateRedisHitRate(array $stats): float
    {
        $hits = $stats['keyspace_hits'] ?? 0;
        $misses = $stats['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? ($hits / $total) * 100 : 0;
    }
    
    protected function checkMissingIndexes(): array
    {
        // Verificar índices específicos de OTP
        $missing = [];
        
        $indexes = DB::select("SHOW INDEX FROM otps");
        $indexNames = array_column($indexes, 'Key_name');
        
        $required = ['idx_usado', 'idx_email_validation', 'idx_cleanup'];
        
        foreach ($required as $index) {
            if (!in_array($index, $indexNames)) {
                $missing[] = $index;
            }
        }
        
        return $missing;
    }
    
    protected function clearScreen(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            system('cls');
        } else {
            system('clear');
        }
    }
}