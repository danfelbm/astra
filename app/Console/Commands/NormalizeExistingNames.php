<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class NormalizeExistingNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:normalize-names 
                            {--dry-run : Simular el proceso sin hacer cambios}
                            {--show-changes : Mostrar cada cambio realizado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normaliza los nombres de usuarios existentes a formato Title Case (Primera letra mayúscula)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $showChanges = $this->option('show-changes');
        
        $this->info('========================================');
        $this->info('  Normalización de Nombres de Usuarios');
        $this->info('========================================');
        
        if ($isDryRun) {
            $this->warn('⚠️  Modo DRY RUN activado - No se harán cambios reales');
        }
        
        // Contar total de usuarios
        $totalUsers = User::count();
        $this->info("Total de usuarios a procesar: {$totalUsers}");
        $this->newLine();
        
        $processedCount = 0;
        $changedCount = 0;
        $errorCount = 0;
        
        // Crear barra de progreso
        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->start();
        
        // Procesar usuarios en chunks para optimizar memoria
        User::chunk(100, function ($users) use (&$processedCount, &$changedCount, &$errorCount, $isDryRun, $showChanges, $progressBar) {
            foreach ($users as $user) {
                try {
                    $originalName = $user->name;
                    
                    // Normalizar el nombre usando la misma lógica del mutator
                    $normalizedName = $originalName ? Str::title(mb_strtolower(trim($originalName))) : $originalName;
                    
                    // Solo actualizar si el nombre cambió
                    if ($originalName !== $normalizedName) {
                        if (!$isDryRun) {
                            // Actualizar directamente en la BD para evitar triggers adicionales
                            $user->update(['name' => $normalizedName]);
                        }
                        
                        $changedCount++;
                        
                        if ($showChanges) {
                            $progressBar->clear();
                            $this->line("  📝 {$originalName} → {$normalizedName}");
                            $progressBar->display();
                        }
                    }
                    
                    $processedCount++;
                    $progressBar->advance();
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $progressBar->clear();
                    $this->error("  ❌ Error procesando usuario ID {$user->id}: {$e->getMessage()}");
                    $progressBar->display();
                }
            }
        });
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Mostrar resumen
        $this->info('========================================');
        $this->info('           RESUMEN DE PROCESO');
        $this->info('========================================');
        $this->info("✅ Usuarios procesados: {$processedCount}");
        $this->info("📝 Nombres normalizados: {$changedCount}");
        $this->info("⏭️  Sin cambios: " . ($processedCount - $changedCount));
        
        if ($errorCount > 0) {
            $this->error("❌ Errores encontrados: {$errorCount}");
        }
        
        if ($isDryRun) {
            $this->newLine();
            $this->warn('ℹ️  Este fue un DRY RUN - No se realizaron cambios reales');
            $this->info('Para aplicar los cambios, ejecuta el comando sin --dry-run');
        } else {
            $this->newLine();
            $this->info('✨ Proceso completado exitosamente!');
        }
        
        return Command::SUCCESS;
    }
}
