<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\Core\SendOTPEmailJob;
use App\Jobs\Core\SendOTPWhatsAppJob;
use App\Services\Core\QueueRateLimiterService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class TestRateLimitingStress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:rate-limiting 
                            {--type=email : Tipo de test (email, whatsapp, both)}
                            {--count=20 : Número de jobs a generar}
                            {--rate=10 : Jobs por segundo a generar}
                            {--duration=0 : Duración en segundos (0 = usar count)}
                            {--monitor : Ejecutar monitor durante el test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test de carga y rendimiento para rate limiting de OTP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $count = (int) $this->option('count');
        $rate = (int) $this->option('rate');
        $duration = (int) $this->option('duration');
        $monitor = $this->option('monitor');
        
        $this->info("🚀 Iniciando test de rate limiting");
        $this->line("Tipo: {$type}");
        $this->line("Jobs: {$count}");
        $this->line("Rate: {$rate}/seg");
        
        if ($monitor) {
            $this->info("📊 Iniciando monitor en background...");
            $monitorProcess = proc_open(
                'php artisan otp:monitor --interval=1',
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            sleep(2); // Dar tiempo al monitor para iniciar
        }
        
        $this->newLine();
        $this->info("⏱️ Iniciando generación de jobs...");
        
        $stats = $this->generateJobs($type, $count, $rate, $duration);
        
        $this->newLine();
        $this->info("✅ Test completado");
        $this->displayStats($stats);
        
        if (isset($monitorProcess) && is_resource($monitorProcess)) {
            $this->info("⏹️ Deteniendo monitor...");
            proc_terminate($monitorProcess);
            proc_close($monitorProcess);
        }
        
        return 0;
    }
    
    /**
     * Generar jobs de test según configuración
     */
    private function generateJobs(string $type, int $count, int $rate, int $duration): array
    {
        $stats = [
            'generated' => 0,
            'start_time' => Carbon::now(),
            'emails' => 0,
            'whatsapp' => 0,
            'errors' => 0
        ];
        
        $intervalMs = 1000 / $rate; // Milisegundos entre jobs
        $useCount = $duration === 0;
        $endTime = $useCount ? null : Carbon::now()->addSeconds($duration);
        
        $progressBar = $this->output->createProgressBar($useCount ? $count : $duration);
        $progressBar->start();
        
        $jobCounter = 0;
        
        while (($useCount && $jobCounter < $count) || (!$useCount && Carbon::now() < $endTime)) {
            $startLoop = microtime(true);
            
            try {
                switch ($type) {
                    case 'email':
                        $this->dispatchEmailJob($jobCounter, $stats);
                        break;
                    case 'whatsapp':
                        $this->dispatchWhatsAppJob($jobCounter, $stats);
                        break;
                    case 'both':
                        if ($jobCounter % 2 === 0) {
                            $this->dispatchEmailJob($jobCounter, $stats);
                        } else {
                            $this->dispatchWhatsAppJob($jobCounter, $stats);
                        }
                        break;
                }
                
                $stats['generated']++;
                $jobCounter++;
                $progressBar->advance();
                
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->error("Error en job {$jobCounter}: " . $e->getMessage());
            }
            
            // Controlar rate limiting
            $elapsed = (microtime(true) - $startLoop) * 1000;
            $sleepMs = max(0, $intervalMs - $elapsed);
            
            if ($sleepMs > 0) {
                usleep($sleepMs * 1000); // usleep usa microsegundos
            }
        }
        
        $progressBar->finish();
        $stats['end_time'] = Carbon::now();
        
        return $stats;
    }
    
    /**
     * Dispatch job de email OTP
     */
    private function dispatchEmailJob(int $counter, array &$stats): void
    {
        $email = "stress-test-{$counter}@example.com";
        $codigo = str_pad($counter % 1000000, 6, '0', STR_PAD_LEFT);
        
        SendOTPEmailJob::dispatch($email, $codigo, "Usuario Test {$counter}", 10);
        $stats['emails']++;
    }
    
    /**
     * Dispatch job de WhatsApp OTP
     */
    private function dispatchWhatsAppJob(int $counter, array &$stats): void
    {
        $phone = "1111111" . str_pad($counter % 10000, 4, '0', STR_PAD_LEFT);
        $codigo = str_pad($counter % 1000000, 6, '0', STR_PAD_LEFT);
        
        SendOTPWhatsAppJob::dispatch($phone, $codigo, "Usuario Test {$counter}", 10);
        $stats['whatsapp']++;
    }
    
    /**
     * Mostrar estadísticas del test
     */
    private function displayStats(array $stats): void
    {
        $duration = $stats['start_time']->diffInMilliseconds($stats['end_time']) / 1000;
        $actualRate = $stats['generated'] / $duration;
        
        $this->newLine(2);
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Jobs generados', number_format($stats['generated'])],
                ['Jobs email', number_format($stats['emails'])],
                ['Jobs WhatsApp', number_format($stats['whatsapp'])],
                ['Errores', number_format($stats['errors'])],
                ['Duración', number_format($duration, 2) . ' segundos'],
                ['Rate real', number_format($actualRate, 2) . ' jobs/seg'],
                ['Inicio', $stats['start_time']->format('H:i:s.v')],
                ['Fin', $stats['end_time']->format('H:i:s.v')],
            ]
        );
        
        // Mostrar estado actual de colas
        $this->newLine();
        $this->info("📊 Estado actual de colas:");
        
        try {
            $queueStats = [
                'default' => $this->getQueueSize('default'),
                'otp-emails' => $this->getQueueSize('otp-emails'),
                'otp-whatsapp' => $this->getQueueSize('otp-whatsapp'),
            ];
            
            foreach ($queueStats as $queue => $size) {
                $this->line("  {$queue}: {$size} jobs pendientes");
            }
            
        } catch (\Exception $e) {
            $this->warn("No se pudo obtener estadísticas de colas: " . $e->getMessage());
        }
        
        $this->newLine();
        $this->comment("💡 Usa 'php artisan otp:monitor' para ver el procesamiento en tiempo real");
        $this->comment("💡 Usa 'php artisan queue:work --queue=otp-emails,otp-whatsapp' para procesar");
    }
    
    /**
     * Obtener tamaño de cola
     */
    private function getQueueSize(string $queueName): int
    {
        return DB::table('jobs')->where('queue', $queueName)->count();
    }
}