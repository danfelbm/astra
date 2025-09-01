<?php

namespace App\Console\Commands;

use App\Models\Votaciones\UrnaSession;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanExpiredUrnaSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'urna:cleanup 
                            {--dry-run : Mostrar qué sesiones se limpiarían sin eliminarlas}
                            {--days= : Número de días para mantener sesiones expiradas (por defecto desde config)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia sesiones de urna expiradas y actualiza su estado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $days = $this->option('days') ?? config('votaciones.urna_cleanup_after_days', 7);
        
        $this->info('🗳️ Iniciando limpieza de sesiones de urna...');
        
        // 1. Actualizar sesiones activas que han expirado
        $expiredSessions = UrnaSession::expiredForCleanup()->get();
        $expiredCount = $expiredSessions->count();
        
        if ($expiredCount > 0) {
            $this->info("📋 Encontradas {$expiredCount} sesiones activas expiradas");
            
            if (!$dryRun) {
                foreach ($expiredSessions as $session) {
                    $session->expire();
                    $this->line("  ✓ Eliminada sesión ID: {$session->id} - Usuario: {$session->usuario_id}");
                }
                $this->info("✅ {$expiredCount} sesiones eliminadas");
            } else {
                $this->warn("  [DRY RUN] Se eliminarían {$expiredCount} sesiones expiradas");
            }
        } else {
            $this->info("✓ No hay sesiones activas expiradas");
        }
        
        // 2. NUEVO: Limpiar sesiones huérfanas (activas sin voto después de tiempo prudencial)
        // Sesiones que están activas pero han pasado más de 10 minutos desde su expiración
        // y el usuario no tiene voto registrado
        $orphanedSessions = UrnaSession::where('status', 'active')
            ->where('expires_at', '<', Carbon::now()->subMinutes(10))
            ->whereDoesntHave('votacion.votos', function($query) {
                $query->whereColumn('votos.usuario_id', 'urna_sessions.usuario_id');
            })
            ->get();
        
        $orphanedCount = $orphanedSessions->count();
        
        if ($orphanedCount > 0) {
            $this->info("🔍 Encontradas {$orphanedCount} sesiones huérfanas (activas sin voto asociado)");
            
            if (!$dryRun) {
                foreach ($orphanedSessions as $session) {
                    // Registrar en audit log antes de eliminar
                    $session->logAction('expiró por sesión huérfana', 
                        'Sesión eliminada - activa sin voto después de 10 minutos post-expiración');
                    
                    Log::info('Sesión huérfana eliminada', [
                        'session_id' => $session->id,
                        'votacion_id' => $session->votacion_id,
                        'usuario_id' => $session->usuario_id,
                        'opened_at' => $session->opened_at,
                        'expires_at' => $session->expires_at,
                    ]);
                    
                    // Eliminar la sesión huérfana completamente
                    $session->delete();
                    
                    $this->line("  ✓ Eliminada sesión huérfana ID: {$session->id} - Usuario: {$session->usuario_id}");
                }
                $this->info("✅ {$orphanedCount} sesiones huérfanas eliminadas");
            } else {
                $this->warn("  [DRY RUN] Se eliminarían {$orphanedCount} sesiones huérfanas");
            }
        } else {
            $this->info("✓ No hay sesiones huérfanas");
        }
        
        // 3. Eliminar sesiones antiguas (más de X días)
        $cutoffDate = Carbon::now()->subDays($days);
        $oldSessions = UrnaSession::where('status', '!=', 'active')
            ->where('created_at', '<', $cutoffDate)
            ->get();
        $oldCount = $oldSessions->count();
        
        if ($oldCount > 0) {
            $this->info("🗑️ Encontradas {$oldCount} sesiones antiguas (más de {$days} días)");
            
            if (!$dryRun) {
                // Registrar en log antes de eliminar
                foreach ($oldSessions as $session) {
                    Log::info('Eliminando sesión de urna antigua', [
                        'session_id' => $session->id,
                        'votacion_id' => $session->votacion_id,
                        'usuario_id' => $session->usuario_id,
                        'status' => $session->status,
                        'opened_at' => $session->opened_at,
                        'closed_at' => $session->closed_at,
                    ]);
                }
                
                $deleted = UrnaSession::where('status', '!=', 'active')
                    ->where('created_at', '<', $cutoffDate)
                    ->delete();
                    
                $this->info("✅ {$deleted} sesiones antiguas eliminadas");
            } else {
                $this->warn("  [DRY RUN] Se eliminarían {$oldCount} sesiones antiguas");
            }
        } else {
            $this->info("✓ No hay sesiones antiguas para eliminar");
        }
        
        // 3. Mostrar estadísticas
        $this->newLine();
        $this->info('📊 Estadísticas actuales:');
        
        $stats = [
            'Sesiones activas' => UrnaSession::where('status', 'active')->count(),
            'Sesiones con voto' => UrnaSession::where('status', 'voted')->count(),
            'Sesiones eliminadas (total histórico)' => 0, // Ya no existen sesiones 'expired', se eliminan completamente
            'Total de sesiones' => UrnaSession::count(),
        ];
        
        foreach ($stats as $label => $count) {
            $this->line("  • {$label}: {$count}");
        }
        
        $this->newLine();
        $this->info('✅ Limpieza completada');
        
        return Command::SUCCESS;
    }
}