<?php

namespace Modules\Configuration\Console\Commands;

use Modules\Core\Services\QueueRateLimiterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorOTPQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:monitor 
                            {--interval=5 : Intervalo de actualización en segundos}
                            {--metrics : Mostrar métricas históricas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorear el estado de las colas OTP en tiempo real';

    protected QueueRateLimiterService $rateLimiter;

    /**
     * Create a new command instance.
     */
    public function __construct(QueueRateLimiterService $rateLimiter)
    {
        parent::__construct();
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('metrics')) {
            $this->showMetrics();
            return 0;
        }

        $interval = (int) $this->option('interval');
        
        $this->info('Monitoreando colas OTP... (Ctrl+C para salir)');
        $this->newLine();
        
        while (true) {
            // Limpiar pantalla
            $this->clearScreen();
            
            // Mostrar header
            $this->displayHeader();
            
            // Obtener estadísticas
            $stats = $this->rateLimiter->getQueueStats();
            
            // Mostrar estadísticas de Email
            $this->displayQueueStats('📧 Cola de Email OTP', $stats['email']);
            
            // Mostrar estadísticas de WhatsApp
            $this->displayQueueStats('💬 Cola de WhatsApp OTP', $stats['whatsapp']);
            
            // Mostrar resumen total
            $this->displayTotalStats($stats['total']);
            
            // Mostrar jobs recientes
            $this->displayRecentJobs();
            
            // Mostrar timestamp de actualización
            $this->info('Última actualización: ' . now()->format('Y-m-d H:i:s'));
            $this->info("Actualizando cada {$interval} segundos...");
            
            // Esperar antes de la siguiente actualización
            sleep($interval);
        }
        
        return 0;
    }
    
    /**
     * Limpiar pantalla de consola
     */
    protected function clearScreen(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }
    
    /**
     * Mostrar header del monitor
     */
    protected function displayHeader(): void
    {
        $this->line('═══════════════════════════════════════════════════════════════════');
        $this->line('                    MONITOR DE COLAS OTP                          ');
        $this->line('═══════════════════════════════════════════════════════════════════');
        $this->newLine();
    }
    
    /**
     * Mostrar estadísticas de una cola
     */
    protected function displayQueueStats(string $title, array $stats): void
    {
        $this->line($title);
        $this->line('───────────────────────────────────────────────────────────────────');
        
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Cola', $stats['queue_name']],
                ['Pendientes', $this->formatNumber($stats['pending'])],
                ['Procesando', $this->formatNumber($stats['processing'])],
                ['Fallidos (24h)', $this->formatNumber($stats['failed'])],
                ['Rate Limit', $stats['rate_limit'] . '/seg'],
                ['Throttle Status', $this->formatThrottleStatus($stats['throttle_status'])],
            ]
        );
        
        // Mostrar barra de progreso visual
        if ($stats['pending'] > 0) {
            $this->displayProgressBar($stats['pending'], $stats['rate_limit']);
        }
        
        $this->newLine();
    }
    
    /**
     * Formatear estado de throttle
     */
    protected function formatThrottleStatus(array $throttle): string
    {
        if ($throttle['allowed']) {
            return "✅ Disponible ({$throttle['remaining']}/{$throttle['limit']})";
        } else {
            return "⏸️ Rate limit (retry en {$throttle['retryAfter']}s)";
        }
    }
    
    /**
     * Mostrar barra de progreso
     */
    protected function displayProgressBar(int $pending, int $rateLimit): void
    {
        $estimatedSeconds = (int) ($pending / $rateLimit);
        $estimatedTime = $this->formatTime($estimatedSeconds);
        
        $this->info("⏱️ Tiempo estimado para procesar cola: {$estimatedTime}");
        
        // Crear barra visual
        $barLength = 50;
        $processed = 0; // Podríamos trackear esto en el futuro
        $total = $pending + $processed;
        
        if ($total > 0) {
            $percentage = ($processed / $total) * 100;
            $filled = (int) (($processed / $total) * $barLength);
            $empty = $barLength - $filled;
            
            $bar = str_repeat('█', $filled) . str_repeat('░', $empty);
            $this->line("[{$bar}] {$percentage}%");
        }
    }
    
    /**
     * Mostrar estadísticas totales
     */
    protected function displayTotalStats(array $total): void
    {
        $this->line('📊 Resumen Total');
        $this->line('───────────────────────────────────────────────────────────────────');
        
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total Pendientes', $this->formatNumber($total['pending'])],
                ['Total Procesando', $this->formatNumber($total['processing'])],
                ['Total Fallidos (24h)', $this->formatNumber($total['failed'])],
            ]
        );
        
        $this->newLine();
    }
    
    /**
     * Mostrar jobs recientes
     */
    protected function displayRecentJobs(): void
    {
        $this->line('🕒 Jobs Recientes');
        $this->line('───────────────────────────────────────────────────────────────────');
        
        // Obtener últimos 5 jobs procesados
        $recentJobs = DB::table('jobs')
            ->whereIn('queue', [
                config('queue.otp_email_queue', 'otp-emails'),
                config('queue.otp_whatsapp_queue', 'otp-whatsapp')
            ])
            ->whereNotNull('reserved_at')
            ->orderBy('reserved_at', 'desc')
            ->limit(5)
            ->get();
        
        if ($recentJobs->isEmpty()) {
            $this->info('No hay jobs procesándose actualmente');
        } else {
            $data = [];
            foreach ($recentJobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobClass = $payload['displayName'] ?? 'Unknown';
                $queue = $job->queue;
                $reserved = \Carbon\Carbon::parse($job->reserved_at);
                $processing = $reserved->diffForHumans();
                
                $data[] = [$jobClass, $queue, $processing];
            }
            
            $this->table(['Job', 'Cola', 'Procesando desde'], $data);
        }
        
        $this->newLine();
    }
    
    /**
     * Mostrar métricas históricas
     */
    protected function showMetrics(): void
    {
        $this->info('📈 Métricas de las últimas 24 horas');
        $this->newLine();
        
        $metrics = $this->rateLimiter->getMetrics();
        
        // Mostrar métricas de Email
        $this->line('📧 Métricas de Email OTP');
        $this->line('───────────────────────────────────────────────────────────────────');
        
        $emailData = [];
        foreach ($metrics['email'] as $metric) {
            $emailData[] = [
                $metric['hour'],
                $this->formatNumber($metric['sent']),
                $this->formatNumber($metric['throttled']),
                $metric['success_rate'] . '%',
                $this->formatTime($metric['delay_seconds'])
            ];
        }
        
        $this->table(
            ['Hora', 'Enviados', 'Throttled', 'Tasa éxito', 'Delay total'],
            array_slice($emailData, -10) // Mostrar últimas 10 horas
        );
        
        $this->newLine();
        
        // Mostrar métricas de WhatsApp
        $this->line('💬 Métricas de WhatsApp OTP');
        $this->line('───────────────────────────────────────────────────────────────────');
        
        $whatsappData = [];
        foreach ($metrics['whatsapp'] as $metric) {
            $whatsappData[] = [
                $metric['hour'],
                $this->formatNumber($metric['sent']),
                $this->formatNumber($metric['throttled']),
                $metric['success_rate'] . '%',
                $this->formatTime($metric['delay_seconds'])
            ];
        }
        
        $this->table(
            ['Hora', 'Enviados', 'Throttled', 'Tasa éxito', 'Delay total'],
            array_slice($whatsappData, -10) // Mostrar últimas 10 horas
        );
    }
    
    /**
     * Formatear números con separadores de miles
     */
    protected function formatNumber(int $number): string
    {
        return number_format($number, 0, ',', '.');
    }
    
    /**
     * Formatear tiempo en formato legible
     */
    protected function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return $remainingSeconds > 0 
                ? "{$minutes}m {$remainingSeconds}s"
                : "{$minutes}m";
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $remainingMinutes > 0
            ? "{$hours}h {$remainingMinutes}m"
            : "{$hours}h";
    }
}