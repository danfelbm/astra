<?php

namespace Modules\Campanas\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campanas\Models\Campana;
use Modules\Campanas\Models\CampanaMetrica;

class RefreshCampanaMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campanas:refresh-metrics {campaña?} {--all : Actualizar todas las campañas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar métricas de campañas que no las tengan o estén desactualizadas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $this->info('Actualizando métricas de todas las campañas...');
            $this->actualizarTodas();
        } elseif ($campanaId = $this->argument('campaña')) {
            $this->info("Actualizando métricas de campaña {$campanaId}...");
            $this->actualizarCampana($campanaId);
        } else {
            $this->info('Buscando campañas sin métricas...');
            $this->actualizarFaltantes();
        }
    }

    /**
     * Actualizar métricas de todas las campañas
     */
    protected function actualizarTodas()
    {
        $campanas = Campana::with('segment')->get();
        $actualizadas = 0;

        foreach ($campanas as $campana) {
            if ($this->procesarCampana($campana)) {
                $actualizadas++;
            }
        }

        $this->info("✅ {$actualizadas} campañas actualizadas");
    }

    /**
     * Actualizar métricas de una campaña específica
     */
    protected function actualizarCampana($campanaId)
    {
        $campana = Campana::with('segment')->find($campanaId);

        if (!$campana) {
            $this->error("Campaña {$campanaId} no encontrada");
            return;
        }

        if ($this->procesarCampana($campana)) {
            $this->info("✅ Campaña {$campanaId} actualizada exitosamente");
        } else {
            $this->warn("⚠️ Campaña {$campanaId} no necesita actualización");
        }
    }

    /**
     * Actualizar campañas que no tienen métricas
     */
    protected function actualizarFaltantes()
    {
        $campanasQuery = Campana::query()
            ->whereDoesntHave('metrica')
            ->orWhereHas('metrica', function ($query) {
                $query->where('total_destinatarios', 0);
            });

        $campanas = $campanasQuery->with('segment')->get();
        
        if ($campanas->isEmpty()) {
            $this->info('No hay campañas sin métricas');
            return;
        }

        $this->info("Encontradas {$campanas->count()} campañas sin métricas");
        $actualizadas = 0;

        foreach ($campanas as $campana) {
            if ($this->procesarCampana($campana)) {
                $actualizadas++;
            }
        }

        $this->info("✅ {$actualizadas} campañas actualizadas");
    }

    /**
     * Procesar una campaña individual
     */
    protected function procesarCampana(Campana $campana): bool
    {
        // Solo procesar si tiene segmento y está en estado borrador o programada
        if (!$campana->segment_id) {
            $this->warn("  Campaña {$campana->id} ({$campana->nombre}) no tiene segmento asignado");
            return false;
        }

        // Solo actualizar campañas que no han sido enviadas
        if (!in_array($campana->estado, ['borrador', 'programada'])) {
            $this->info("  Campaña {$campana->id} ({$campana->nombre}) ya está en proceso o completada");
            return false;
        }

        try {
            $totalDestinatarios = $campana->contarDestinatarios();
            
            if ($campana->metrica) {
                // Actualizar métricas existentes
                $campana->metrica->update([
                    'total_destinatarios' => $totalDestinatarios
                ]);
                $this->info("  📊 Actualizada campaña {$campana->id} ({$campana->nombre}): {$totalDestinatarios} destinatarios");
            } else {
                // Crear nuevas métricas
                CampanaMetrica::create([
                    'campana_id' => $campana->id,
                    'tenant_id' => $campana->tenant_id,
                    'total_destinatarios' => $totalDestinatarios,
                    'total_pendientes' => 0,
                    'total_enviados' => 0,
                    'total_fallidos' => 0,
                ]);
                $this->info("  ✨ Creadas métricas para campaña {$campana->id} ({$campana->nombre}): {$totalDestinatarios} destinatarios");
            }

            return true;
        } catch (\Exception $e) {
            $this->error("  ❌ Error procesando campaña {$campana->id}: {$e->getMessage()}");
            return false;
        }
    }
}