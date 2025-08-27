<?php

namespace App\Console\Commands\Imports;

use App\Models\Core\CsvImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorCsvImport extends Command
{
    protected $signature = 'csv:monitor {id? : ID de la importación a monitorear}';
    
    protected $description = 'Monitorea el progreso de importaciones CSV y diagnostica problemas';
    
    public function handle()
    {
        $importId = $this->argument('id');
        
        if ($importId) {
            $this->monitorSpecificImport($importId);
        } else {
            $this->showAllImports();
        }
    }
    
    private function monitorSpecificImport($id)
    {
        $import = CsvImport::find($id);
        
        if (!$import) {
            $this->error("Importación #{$id} no encontrada");
            return;
        }
        
        $this->info("📊 MONITOREO DE IMPORTACIÓN #{$id}");
        $this->info("=====================================");
        
        // Monitoreo en tiempo real
        $lastProcessed = 0;
        $stuckCounter = 0;
        $maxStuckIterations = 10;
        
        while ($import->status === 'processing') {
            $import->refresh();
            
            // Calcular velocidad
            $currentProcessed = $import->processed_rows;
            $speed = $currentProcessed - $lastProcessed;
            $percentage = $import->progress_percentage;
            
            // Detectar si está atascado
            if ($speed === 0) {
                $stuckCounter++;
            } else {
                $stuckCounter = 0;
            }
            
            // Información del progreso
            $this->line(sprintf(
                "\r[%s] %s/%s filas (%.1f%%) | ✅ %s | ❌ %s | ⚡ %s filas/seg %s",
                date('H:i:s'),
                number_format($currentProcessed),
                number_format($import->total_rows),
                $percentage,
                number_format($import->successful_rows),
                number_format($import->failed_rows),
                $speed,
                $stuckCounter > 5 ? '⚠️ POSIBLE BLOQUEO' : ''
            ));
            
            // Verificar deadlocks
            if ($stuckCounter >= $maxStuckIterations) {
                $this->newLine();
                $this->warn("⚠️ La importación parece estar bloqueada. Verificando deadlocks...");
                $this->checkForDeadlocks();
                $stuckCounter = 0;
            }
            
            $lastProcessed = $currentProcessed;
            
            // Esperar 1 segundo antes de actualizar
            sleep(1);
            
            // Salir si se completó
            if ($import->status !== 'processing') {
                break;
            }
        }
        
        $this->newLine(2);
        $this->showImportSummary($import);
    }
    
    private function showAllImports()
    {
        $this->info("📋 IMPORTACIONES CSV ACTIVAS");
        $this->info("============================");
        
        $activeImports = CsvImport::whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        if ($activeImports->isEmpty()) {
            $this->info("No hay importaciones activas en este momento.");
            return;
        }
        
        $headers = ['ID', 'Estado', 'Progreso', 'Procesadas', 'Exitosas', 'Fallidas', 'Tiempo'];
        $rows = [];
        
        foreach ($activeImports as $import) {
            $rows[] = [
                $import->id,
                $this->getStatusBadge($import->status),
                sprintf("%.1f%%", $import->progress_percentage),
                number_format($import->processed_rows) . '/' . number_format($import->total_rows),
                number_format($import->successful_rows),
                number_format($import->failed_rows),
                $import->duration ?? 'N/A'
            ];
        }
        
        $this->table($headers, $rows);
        
        $this->newLine();
        $this->info("💡 Usa 'php artisan csv:monitor <id>' para monitorear una importación específica");
    }
    
    private function showImportSummary(CsvImport $import)
    {
        $status = $this->getStatusBadge($import->status);
        
        $this->info("📊 RESUMEN FINAL");
        $this->info("================");
        $this->line("Estado: {$status}");
        $this->line("Total procesadas: " . number_format($import->processed_rows));
        $this->line("Exitosas: " . number_format($import->successful_rows));
        $this->line("Fallidas: " . number_format($import->failed_rows));
        $this->line("Duración: " . ($import->duration ?? 'N/A'));
        
        if ($import->has_errors) {
            $this->newLine();
            $this->error("❌ ERRORES ENCONTRADOS:");
            $errors = array_slice($import->errors ?? [], 0, 10);
            foreach ($errors as $error) {
                $this->line("  • " . $error);
            }
            if (count($import->errors ?? []) > 10) {
                $this->line("  ... y " . (count($import->errors) - 10) . " errores más");
            }
        }
        
        if ($import->has_conflicts) {
            $this->newLine();
            $this->warn("⚠️ CONFLICTOS PENDIENTES: " . $import->conflict_count);
        }
    }
    
    private function checkForDeadlocks()
    {
        try {
            // Verificar procesos bloqueados en MySQL
            $locks = DB::select("
                SELECT 
                    p1.ID as blocking_id,
                    p1.USER as blocking_user,
                    p1.COMMAND as blocking_command,
                    p2.ID as blocked_id,
                    p2.USER as blocked_user,
                    p2.TIME as blocked_time
                FROM information_schema.PROCESSLIST p1
                JOIN information_schema.PROCESSLIST p2 
                WHERE p1.ID IN (
                    SELECT BLOCKING_PID 
                    FROM information_schema.INNODB_LOCK_WAITS
                )
                LIMIT 5
            ");
            
            if (!empty($locks)) {
                $this->error("🔒 DEADLOCKS DETECTADOS:");
                foreach ($locks as $lock) {
                    $this->line(sprintf(
                        "  Proceso %s está bloqueando a proceso %s (esperando %ss)",
                        $lock->blocking_id,
                        $lock->blocked_id,
                        $lock->blocked_time
                    ));
                }
            } else {
                $this->info("No se detectaron deadlocks activos");
            }
            
        } catch (\Exception $e) {
            $this->warn("No se pudo verificar deadlocks: " . $e->getMessage());
        }
    }
    
    private function getStatusBadge(string $status): string
    {
        return match($status) {
            'pending' => '⏳ Pendiente',
            'processing' => '🔄 Procesando',
            'completed' => '✅ Completada',
            'failed' => '❌ Fallida',
            default => $status
        };
    }
}