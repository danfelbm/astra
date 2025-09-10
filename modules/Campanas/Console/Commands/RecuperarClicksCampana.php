<?php

namespace Modules\Campanas\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campanas\Models\CampanaEnvio;

class RecuperarClicksCampana extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campanas:recuperar-clicks {--campana= : ID de la campaña específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recupera datos de clics antiguos que no tienen metadata';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campanaId = $this->option('campana');
        
        // Obtener envíos con clics pero sin metadata
        $query = CampanaEnvio::where('clicks_count', '>', 0)
            ->where(function($q) {
                $q->whereNull('metadata')
                  ->orWhere('metadata', 'NOT LIKE', '%clicks%');
            });
        
        if ($campanaId) {
            $query->where('campana_id', $campanaId);
        }
        
        $envios = $query->get();
        
        $this->info("Encontrados {$envios->count()} envíos con clics sin metadata");
        
        if ($envios->isEmpty()) {
            $this->info('No hay envíos para recuperar');
            return 0;
        }
        
        $bar = $this->output->createProgressBar($envios->count());
        $bar->start();
        
        foreach ($envios as $envio) {
            $metadata = $envio->metadata ?? [];
            
            // Crear estructura básica de clics si no existe
            if (!isset($metadata['clicks'])) {
                $metadata['clicks'] = [];
            }
            if (!isset($metadata['clicks_detail'])) {
                $metadata['clicks_detail'] = [];
            }
            
            // Agregar datos básicos de clics basados en la información disponible
            for ($i = 0; $i < $envio->clicks_count; $i++) {
                // Si no hay registro específico, crear uno básico
                if (!isset($metadata['clicks'][$i])) {
                    $timestamp = $envio->fecha_primer_click 
                        ? $envio->fecha_primer_click->toIso8601String() 
                        : $envio->fecha_ultimo_click->toIso8601String();
                    
                    $metadata['clicks'][] = [
                        'url' => 'Recuperado - URL no disponible',
                        'timestamp' => $timestamp,
                    ];
                    
                    $metadata['clicks_detail'][] = [
                        'url' => 'Recuperado - URL no disponible',
                        'clicked_at' => $timestamp,
                        'user_agent' => 'No disponible (clic anterior a tracking completo)',
                        'ip' => 'No disponible',
                    ];
                }
            }
            
            // Actualizar el envío con la metadata recuperada
            $envio->update(['metadata' => $metadata]);
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('✅ Recuperación completada');
        
        // Mostrar resumen
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Envíos procesados', $envios->count()],
                ['Clics recuperados', $envios->sum('clicks_count')],
            ]
        );
        
        return 0;
    }
}