<?php

namespace App\Console\Commands;

use App\Models\Votaciones\Votacion;
use App\Models\Votaciones\Voto;
use App\Models\Votaciones\UrnaSession;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MonitorVoting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voting:monitor
                            {votacion_id : ID de la votación a monitorear}
                            {--interval=2 : Intervalo de actualización en segundos}
                            {--duration=0 : Duración del monitoreo en segundos (0 = infinito)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorea en tiempo real el progreso de una votación';

    private array $stats = [];
    private $startTime;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $votacionId = $this->argument('votacion_id');
        $interval = (int) $this->option('interval');
        $duration = (int) $this->option('duration');
        
        $votacion = Votacion::find($votacionId);
        if (!$votacion) {
            $this->error("Votación ID {$votacionId} no encontrada");
            return Command::FAILURE;
        }

        $this->startTime = time();
        $endTime = $duration > 0 ? $this->startTime + $duration : PHP_INT_MAX;

        // Limpiar pantalla
        $this->clearScreen();

        $this->info("🔍 MONITOR DE VOTACIÓN EN TIEMPO REAL");
        $this->info("=====================================");
        $this->info("Votación: {$votacion->titulo}");
        $this->info("Estado: {$votacion->estado}");
        $this->info("Presiona Ctrl+C para salir");
        $this->newLine();

        // Loop de monitoreo
        while (time() < $endTime) {
            $this->updateStats($votacion);
            $this->displayDashboard($votacion);
            
            sleep($interval);
            
            // Mover cursor arriba para sobrescribir
            $this->moveCursorUp(20);
        }

        $this->newLine(2);
        $this->info("Monitoreo finalizado");

        return Command::SUCCESS;
    }

    /**
     * Actualizar estadísticas
     */
    private function updateStats(Votacion $votacion): void
    {
        $this->stats = [
            'timestamp' => now()->format('H:i:s'),
            'elapsed_time' => gmdate("H:i:s", time() - $this->startTime),
            
            // Votantes
            'total_votantes' => $votacion->votantes()->count(),
            'votos_emitidos' => Voto::where('votacion_id', $votacion->id)->count(),
            
            // Sesiones de urna
            'sesiones_totales' => UrnaSession::where('votacion_id', $votacion->id)->count(),
            'sesiones_activas' => UrnaSession::where('votacion_id', $votacion->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->count(),
            'sesiones_votadas' => UrnaSession::where('votacion_id', $votacion->id)
                ->where('status', 'voted')
                ->count(),
            'sesiones_eliminadas' => 0, // Ya no existen sesiones 'expired', se eliminan completamente
            
            // Jobs en proceso
            'votos_pendientes' => $this->getVotosPendientes($votacion->id),
            
            // Últimos eventos
            'ultimo_voto' => Voto::where('votacion_id', $votacion->id)
                ->latest()
                ->first(),
            'ultima_sesion' => UrnaSession::where('votacion_id', $votacion->id)
                ->latest()
                ->first(),
                
            // Tasa de procesamiento
            'votos_ultimo_minuto' => $this->getVotosUltimoMinuto($votacion->id),
            'sesiones_ultimo_minuto' => $this->getSesionesUltimoMinuto($votacion->id),
        ];

        // Calcular porcentaje de participación
        if ($this->stats['total_votantes'] > 0) {
            $this->stats['porcentaje_participacion'] = round(
                ($this->stats['votos_emitidos'] / $this->stats['total_votantes']) * 100, 
                2
            );
        } else {
            $this->stats['porcentaje_participacion'] = 0;
        }

        // Calcular TPS (transacciones por segundo)
        $elapsed = time() - $this->startTime;
        if ($elapsed > 0) {
            $this->stats['tps'] = round($this->stats['votos_emitidos'] / $elapsed, 2);
        } else {
            $this->stats['tps'] = 0;
        }
    }

    /**
     * Mostrar dashboard
     */
    private function displayDashboard(Votacion $votacion): void
    {
        // Header con timestamp
        $this->line(sprintf(
            "⏰ %s | Tiempo transcurrido: %s",
            $this->stats['timestamp'],
            $this->stats['elapsed_time']
        ));
        $this->line(str_repeat('─', 60));

        // Estadísticas principales
        $this->info("📊 ESTADÍSTICAS PRINCIPALES");
        
        $this->line(sprintf(
            "  👥 Votantes: %d / %d (%.1f%%)",
            $this->stats['votos_emitidos'],
            $this->stats['total_votantes'],
            $this->stats['porcentaje_participacion']
        ));

        // Barra de progreso
        $this->displayProgressBar(
            $this->stats['votos_emitidos'],
            $this->stats['total_votantes']
        );

        $this->newLine();

        // Sesiones de urna
        $this->info("🗳️ SESIONES DE URNA");
        $this->line(sprintf(
            "  Activas: %d | Votadas: %d | Eliminadas: %d",
            $this->stats['sesiones_activas'],
            $this->stats['sesiones_votadas'],
            $this->stats['sesiones_eliminadas']
        ));

        // Procesamiento
        $this->newLine();
        $this->info("⚡ PROCESAMIENTO");
        $this->line(sprintf(
            "  Votos pendientes: %d",
            $this->stats['votos_pendientes']
        ));
        $this->line(sprintf(
            "  TPS (votos/seg): %.2f",
            $this->stats['tps']
        ));
        $this->line(sprintf(
            "  Último minuto: %d votos, %d sesiones",
            $this->stats['votos_ultimo_minuto'],
            $this->stats['sesiones_ultimo_minuto']
        ));

        // Últimos eventos
        $this->newLine();
        $this->info("📝 ÚLTIMOS EVENTOS");
        
        if ($this->stats['ultimo_voto']) {
            $this->line(sprintf(
                "  Último voto: hace %s",
                $this->stats['ultimo_voto']->created_at->diffForHumans(null, true)
            ));
        }
        
        if ($this->stats['ultima_sesion']) {
            $this->line(sprintf(
                "  Última sesión: hace %s (%s)",
                $this->stats['ultima_sesion']->created_at->diffForHumans(null, true),
                $this->stats['ultima_sesion']->status
            ));
        }

        // Alertas
        $this->displayAlerts();

        // Espaciado para el próximo refresh
        $this->newLine(3);
    }

    /**
     * Mostrar barra de progreso
     */
    private function displayProgressBar(int $current, int $total): void
    {
        if ($total === 0) {
            $this->line("  [Sin votantes asignados]");
            return;
        }

        $percentage = ($current / $total) * 100;
        $barLength = 40;
        $filledLength = (int) (($percentage / 100) * $barLength);
        
        $bar = str_repeat('█', $filledLength) . str_repeat('░', $barLength - $filledLength);
        
        $this->line(sprintf("  [%s] %.1f%%", $bar, $percentage));
    }

    /**
     * Mostrar alertas
     */
    private function displayAlerts(): void
    {
        $alerts = [];

        // Alerta de sesiones activas expiradas
        $expiredActive = UrnaSession::where('votacion_id', $this->argument('votacion_id'))
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->count();
        
        if ($expiredActive > 0) {
            $alerts[] = "⚠️ {$expiredActive} sesiones activas deberían haber expirado";
        }

        // Alerta de discrepancia
        if ($this->stats['sesiones_votadas'] !== $this->stats['votos_emitidos']) {
            $diff = abs($this->stats['sesiones_votadas'] - $this->stats['votos_emitidos']);
            $alerts[] = "⚠️ Discrepancia de {$diff} entre sesiones votadas y votos";
        }

        // Alerta de procesamiento lento
        if ($this->stats['votos_pendientes'] > 10) {
            $alerts[] = "⚠️ Procesamiento lento: {$this->stats['votos_pendientes']} votos en cola";
        }

        if (!empty($alerts)) {
            $this->newLine();
            $this->warn("🚨 ALERTAS");
            foreach ($alerts as $alert) {
                $this->line("  " . $alert);
            }
        }
    }

    /**
     * Obtener votos pendientes de procesamiento
     */
    private function getVotosPendientes(int $votacionId): int
    {
        $count = 0;
        
        // Buscar en cache los votos marcados como pending o processing
        $votantes = DB::table('votacion_usuario')
            ->where('votacion_id', $votacionId)
            ->pluck('usuario_id');
        
        foreach ($votantes as $usuarioId) {
            $cacheKey = "vote_status_{$votacionId}_{$usuarioId}";
            $status = Cache::get($cacheKey);
            
            if (in_array($status, ['pending', 'processing'])) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Obtener votos del último minuto
     */
    private function getVotosUltimoMinuto(int $votacionId): int
    {
        return Voto::where('votacion_id', $votacionId)
            ->where('created_at', '>=', now()->subMinute())
            ->count();
    }

    /**
     * Obtener sesiones del último minuto
     */
    private function getSesionesUltimoMinuto(int $votacionId): int
    {
        return UrnaSession::where('votacion_id', $votacionId)
            ->where('created_at', '>=', now()->subMinute())
            ->count();
    }

    /**
     * Limpiar pantalla
     */
    private function clearScreen(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }

    /**
     * Mover cursor arriba
     */
    private function moveCursorUp(int $lines): void
    {
        echo "\033[{$lines}A";
    }
}