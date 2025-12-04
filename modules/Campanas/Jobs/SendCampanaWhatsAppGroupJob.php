<?php

namespace Modules\Campanas\Jobs;

use Modules\Campanas\Models\CampanaEnvio;
use Modules\Core\Services\WhatsAppService;
use Modules\Core\Jobs\Middleware\RateLimited;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para enviar mensajes de WhatsApp a grupos
 * Similar a SendCampanaWhatsAppJob pero sin personalización de usuario
 */
class SendCampanaWhatsAppGroupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tries = 3;
    protected $timeout = 30;
    public $maxExceptions = 2;
    public $backoff = [10, 30, 60]; // Reintentos con backoff incremental

    /**
     * Constructor
     */
    public function __construct(
        protected CampanaEnvio $envio
    ) {
        $this->onQueue(config('campanas.queues.whatsapp', 'otp-whatsapp'));
    }

    /**
     * Obtener middleware del job
     */
    public function middleware(): array
    {
        return [
            RateLimited::forCampanaWhatsApp(), // Factory method específico para campañas de WhatsApp
        ];
    }

    /**
     * Ejecutar el job
     */
    public function handle(WhatsAppService $whatsAppService): void
    {
        try {
            // Verificar que el envío esté en estado correcto
            if (!in_array($this->envio->estado, ['pendiente', 'enviando'])) {
                Log::warning("Envío de grupo {$this->envio->id} no está en estado válido para enviar: {$this->envio->estado}");
                return;
            }

            // Cargar relaciones necesarias
            $this->envio->load(['campana.plantillaWhatsApp']);

            // Verificar que tengamos plantilla de WhatsApp
            if (!$this->envio->campana->plantillaWhatsApp) {
                throw new \Exception("No hay plantilla de WhatsApp configurada para la campaña");
            }

            // El destinatario es el JID del grupo
            $groupJid = $this->envio->destinatario;

            if (!$groupJid || !str_ends_with($groupJid, '@g.us')) {
                $this->marcarComoFallido("JID de grupo inválido: {$groupJid}");
                return;
            }

            // Procesar contenido SIN variables de usuario (grupos no soportan personalización)
            $mensaje = $this->envio->campana->plantillaWhatsApp->contenido;

            // Agregar footer si está habilitado tracking
            if ($this->envio->campana->configuracion['tracking_enabled'] ?? true) {
                $mensaje = $this->agregarFooter($mensaje);
            }

            // Metadata del grupo
            $metadata = $this->envio->metadata ?? [];

            // Enviar mensaje al grupo vía WhatsApp
            $resultado = $whatsAppService->sendGroupMessage(
                $groupJid,
                $mensaje,
                [
                    'campana_id' => $this->envio->campana_id,
                    'envio_id' => $this->envio->id,
                    'tracking_id' => $this->envio->tracking_id,
                    'group_nombre' => $metadata['group_nombre'] ?? 'Grupo',
                    'group_participantes' => $metadata['group_participantes'] ?? 0,
                ]
            );

            if (!$resultado['success']) {
                throw new \Exception($resultado['message'] ?? 'Error desconocido al enviar WhatsApp a grupo');
            }

            // Marcar como enviado
            $this->envio->update([
                'estado' => 'enviado',
                'fecha_enviado' => now(),
                'metadata' => array_merge($metadata, [
                    'whatsapp_message_id' => $resultado['message_id'] ?? null,
                    'whatsapp_status' => $resultado['status'] ?? null,
                ]),
            ]);

            // Actualizar métricas
            $this->actualizarMetricas();

        } catch (\Exception $e) {
            $this->manejarError($e);
            throw $e; // Re-lanzar para que el sistema de reintentos maneje
        }
    }

    /**
     * Agregar footer al mensaje
     */
    protected function agregarFooter(string $mensaje): string
    {
        $footer = "\n\n_Este mensaje fue enviado por " . config('app.name') . "_";
        return $mensaje . $footer;
    }

    /**
     * Marcar envío como fallido
     */
    protected function marcarComoFallido(string $mensaje): void
    {
        $this->envio->update([
            'estado' => 'fallido',
            'error_mensaje' => $mensaje,
            'metadata' => array_merge($this->envio->metadata ?? [], [
                'fecha_fallo' => now()->toIso8601String(),
                'intentos' => $this->attempts(),
            ]),
        ]);

        // Actualizar métricas
        $this->actualizarMetricas(true);
    }

    /**
     * Actualizar métricas de la campaña
     */
    protected function actualizarMetricas(bool $fallido = false): void
    {
        $metrica = $this->envio->campana->metrica;

        if (!$metrica) {
            return;
        }

        if ($fallido) {
            $metrica->increment('total_fallidos');
            $metrica->increment('whatsapp_fallidos');
        } else {
            $metrica->increment('total_enviados');
            $metrica->increment('whatsapp_enviados');
            $metrica->increment('whatsapp_entregados'); // Asumimos entregado al enviar
        }

        // Actualizar total pendientes
        $pendientes = $this->envio->campana->envios()
            ->where('estado', 'pendiente')
            ->count();

        $metrica->update([
            'total_pendientes' => $pendientes,
            'ultima_actualizacion' => now(),
        ]);
    }

    /**
     * Manejar errores del envío
     */
    protected function manejarError(\Exception $e): void
    {
        $metadata = $this->envio->metadata ?? [];

        Log::error("Error enviando WhatsApp a grupo de campaña", [
            'envio_id' => $this->envio->id,
            'campana_id' => $this->envio->campana_id,
            'group_jid' => $this->envio->destinatario,
            'group_nombre' => $metadata['group_nombre'] ?? 'N/A',
            'error' => $e->getMessage(),
        ]);

        // Si es el último intento, marcar como fallido
        if ($this->attempts() >= $this->tries) {
            $this->marcarComoFallido($e->getMessage());
        } else {
            // Actualizar metadata para reintento
            $this->envio->update([
                'metadata' => array_merge($metadata, [
                    'ultimo_error' => $e->getMessage(),
                    'fecha_ultimo_error' => now()->toIso8601String(),
                    'intentos' => $this->attempts(),
                ]),
            ]);
        }
    }

    /**
     * Manejar fallo definitivo del job
     */
    public function failed(\Throwable $exception): void
    {
        $metadata = $this->envio->metadata ?? [];

        Log::error("Fallo definitivo enviando WhatsApp a grupo de campaña", [
            'envio_id' => $this->envio->id,
            'group_jid' => $this->envio->destinatario,
            'group_nombre' => $metadata['group_nombre'] ?? 'N/A',
            'error' => $exception->getMessage(),
        ]);

        $this->marcarComoFallido("Fallo definitivo: " . $exception->getMessage());
    }

    /**
     * Determinar si el job debe ser reintentado
     */
    public function shouldRetry(\Throwable $exception): bool
    {
        // No reintentar si es un error de validación
        if ($exception instanceof \InvalidArgumentException) {
            return false;
        }

        // No reintentar si el JID es inválido
        if (str_contains($exception->getMessage(), 'JID') &&
            str_contains($exception->getMessage(), 'inválido')) {
            return false;
        }

        return true;
    }

    /**
     * Obtener tags para logging
     */
    public function tags(): array
    {
        $metadata = $this->envio->metadata ?? [];

        return [
            'campana:' . $this->envio->campana_id,
            'envio:' . $this->envio->id,
            'tipo:whatsapp_group',
            'grupo:' . ($metadata['group_nombre'] ?? 'N/A'),
        ];
    }
}
