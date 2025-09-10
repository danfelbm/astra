<?php

namespace Modules\Campanas\Jobs;

use Modules\Campanas\Models\Campana;
use Modules\Campanas\Models\CampanaEnvio;
use Modules\Core\Jobs\Middleware\WithRateLimiting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCampanaBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WithRateLimiting;

    protected $tries = 3;
    protected $timeout = 600; // 10 minutos
    public $maxExceptions = 3;

    /**
     * Constructor
     */
    public function __construct(
        protected Campana $campana,
        protected int $batchNumber = 1
    ) {
        // Configurar la cola según el tipo de campaña
        $this->onQueue('default');
    }

    /**
     * Ejecutar el job
     */
    public function handle(): void
    {
        try {
            // Refrescar el modelo desde la DB por si hay cambios
            $this->campana = $this->campana->fresh();
            
            // Verificar que la campaña puede enviarse
            $puedeEnviarse = $this->campana->puedeEnviarse();
            if (!$puedeEnviarse['puede_enviarse']) {
                $errores = implode(', ', $puedeEnviarse['errores'] ?? []);
                Log::warning("Campaña {$this->campana->id} no puede enviarse: {$errores}");
                return;
            }

            // Actualizar estado si es necesario
            if ($this->campana->estado === 'programada' || $this->campana->estado === 'pausada') {
                $this->campana->update([
                    'estado' => 'enviando',
                    'fecha_inicio' => $this->campana->fecha_inicio ?? now(),
                ]);
            }

            // Obtener configuración de la campaña
            $config = $this->campana->configuracion ?? [];
            $batchSizeEmail = $config['batch_size_email'] ?? config('campanas.batch_size.email');
            $batchSizeWhatsApp = $config['batch_size_whatsapp'] ?? config('campanas.batch_size.whatsapp');

            // Procesar envíos de email si aplica
            if (in_array($this->campana->tipo, ['email', 'ambos'])) {
                $this->procesarEmailBatch($batchSizeEmail);
            }

            // Procesar envíos de WhatsApp si aplica
            if (in_array($this->campana->tipo, ['whatsapp', 'ambos'])) {
                $this->procesarWhatsAppBatch($batchSizeWhatsApp);
            }

            // Verificar si hay más envíos pendientes o en proceso
            $pendientes = $this->campana->envios()
                ->where('estado', 'pendiente')
                ->count();
                
            $enviando = $this->campana->envios()
                ->where('estado', 'enviando')
                ->count();
                
            $totalPorProcesar = $pendientes + $enviando;
            
            Log::info("Estado de campaña {$this->campana->id}: {$pendientes} pendientes, {$enviando} enviando");

            if ($pendientes > 0) {
                // Si hay pendientes, programar el siguiente batch
                $delay = now()->addSeconds(30); // Esperar 30 segundos entre batches
                
                dispatch(new self($this->campana, $this->batchNumber + 1))
                    ->delay($delay);
                    
                $nextBatch = $this->batchNumber + 1;
                Log::info("Programado batch #{$nextBatch} para campaña {$this->campana->id} con {$pendientes} envíos pendientes");
            } else if ($enviando > 0) {
                // Si no hay pendientes pero hay envíos en proceso, esperar más tiempo y verificar de nuevo
                $delay = now()->addSeconds(60); // Esperar 60 segundos para que terminen los envíos en proceso
                
                dispatch(new self($this->campana, $this->batchNumber + 1))
                    ->delay($delay);
                    
                Log::info("Esperando finalización de {$enviando} envíos en proceso para campaña {$this->campana->id}");
            } else {
                // Solo marcar como completada si NO hay pendientes NI enviando
                Log::info("Campaña {$this->campana->id} realmente completada - Sin pendientes ni enviando");
                $this->finalizarCampana();
            }

        } catch (\Exception $e) {
            Log::error("Error procesando batch de campaña {$this->campana->id}: {$e->getMessage()}", [
                'batch' => $this->batchNumber,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-lanzar para que el sistema de reintentos maneje el error
            throw $e;
        }
    }

    /**
     * Procesar batch de emails
     */
    protected function procesarEmailBatch(int $batchSize): void
    {
        // Obtener envíos pendientes de email
        $envios = $this->campana->envios()
            ->where('tipo', 'email')
            ->where('estado', 'pendiente')
            ->take($batchSize)
            ->get();

        Log::info("Procesando {$envios->count()} emails del batch #{$this->batchNumber} para campaña {$this->campana->id}");

        foreach ($envios as $envio) {
            // Marcar como enviando
            $envio->update(['estado' => 'enviando']);
            
            // Despachar job individual con delay aleatorio pequeño (0-2 segundos)
            $delay = now()->addMilliseconds(rand(0, 2000));
            
            dispatch(new SendCampanaEmailJob($envio))
                ->onQueue(config('campanas.queues.email'))
                ->delay($delay);
        }
    }

    /**
     * Procesar batch de WhatsApp
     */
    protected function procesarWhatsAppBatch(int $batchSize): void
    {
        // Obtener envíos pendientes de WhatsApp
        $envios = $this->campana->envios()
            ->where('tipo', 'whatsapp')
            ->where('estado', 'pendiente')
            ->take($batchSize)
            ->get();

        Log::info("Procesando {$envios->count()} WhatsApp del batch #{$this->batchNumber} para campaña {$this->campana->id}");

        // Obtener configuración de delays
        $config = $this->campana->configuracion ?? [];
        $minDelay = $config['whatsapp_min_delay'] ?? config('campanas.whatsapp.delay_min');
        $maxDelay = $config['whatsapp_max_delay'] ?? config('campanas.whatsapp.delay_max');

        $totalDelay = 0;
        foreach ($envios as $index => $envio) {
            // Marcar como enviando
            $envio->update(['estado' => 'enviando']);
            
            // Calcular delay aleatorio acumulativo
            $randomDelay = rand($minDelay * 1000, $maxDelay * 1000); // Convertir a milisegundos
            $totalDelay += $randomDelay;
            
            // Despachar job individual con delay acumulativo
            dispatch(new SendCampanaWhatsAppJob($envio))
                ->onQueue(config('campanas.queues.whatsapp'))
                ->delay(now()->addMilliseconds($totalDelay));
                
            Log::debug("WhatsApp #{$index} programado con delay de {$totalDelay}ms");
        }
    }

    /**
     * Finalizar campaña
     */
    protected function finalizarCampana(): void
    {
        $this->campana->update([
            'estado' => 'completada',
            'fecha_fin' => now(),
        ]);

        // Actualizar métricas finales
        $this->campana->metrica?->actualizarMetricas();

        Log::info("Campaña {$this->campana->id} completada exitosamente");

        // Notificar al creador (opcional)
        // Notification::send($this->campana->createdBy, new CampanaCompletadaNotification($this->campana));
    }

    /**
     * Manejar fallo del job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Fallo crítico en ProcessCampanaBatchJob para campaña {$this->campana->id}", [
            'batch' => $this->batchNumber,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Pausar la campaña en caso de fallo crítico
        $this->campana->update([
            'estado' => 'pausada',
            'metadata' => array_merge($this->campana->metadata ?? [], [
                'ultimo_error' => $exception->getMessage(),
                'batch_fallido' => $this->batchNumber,
                'fecha_error' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Obtener tags para logging
     */
    public function tags(): array
    {
        return [
            'campana:' . $this->campana->id,
            'batch:' . $this->batchNumber,
            'tipo:' . $this->campana->tipo,
        ];
    }
}