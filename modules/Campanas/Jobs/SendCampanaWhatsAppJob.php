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

class SendCampanaWhatsAppJob implements ShouldQueue
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
                Log::warning("Envío {$this->envio->id} no está en estado válido para enviar: {$this->envio->estado}");
                return;
            }

            // Cargar relaciones necesarias
            $this->envio->load(['campana.plantillaWhatsApp', 'user']);

            // Verificar que tengamos plantilla de WhatsApp
            if (!$this->envio->campana->plantillaWhatsApp) {
                throw new \Exception("No hay plantilla de WhatsApp configurada para la campaña");
            }

            // Verificar que el usuario tenga teléfono
            $numeroTelefono = $this->envio->user->telefono;
            
            if (!$numeroTelefono) {
                $this->marcarComoFallido("Usuario sin teléfono configurado");
                return;
            }

            // Normalizar número de teléfono
            $telefono = $this->normalizarTelefono($numeroTelefono);
            if (!$telefono) {
                $this->marcarComoFallido("Número de teléfono inválido: {$numeroTelefono}");
                return;
            }

            // Procesar contenido con variables del usuario
            $mensaje = $this->envio->campana->plantillaWhatsApp->procesarContenido($this->envio->user);

            // Agregar tracking si está habilitado
            if ($this->envio->campana->configuracion['tracking_enabled'] ?? true) {
                $mensaje = $this->agregarTrackingWhatsApp($mensaje);
            }

            // Enviar mensaje vía WhatsApp
            $resultado = $whatsAppService->enviarMensaje(
                $telefono,
                $mensaje,
                [
                    'campana_id' => $this->envio->campana_id,
                    'envio_id' => $this->envio->id,
                    'tracking_id' => $this->envio->tracking_id,
                ]
            );

            if (!$resultado['success']) {
                throw new \Exception($resultado['message'] ?? 'Error desconocido al enviar WhatsApp');
            }

            // Marcar como enviado
            $this->envio->update([
                'estado' => 'enviado',
                'fecha_enviado' => now(),
                'metadata' => array_merge($this->envio->metadata ?? [], [
                    'whatsapp_message_id' => $resultado['message_id'] ?? null,
                    'whatsapp_status' => $resultado['status'] ?? null,
                ]),
            ]);

            // Actualizar métricas (sin log individual - solo errores se registran)
            $this->actualizarMetricas();

        } catch (\Exception $e) {
            $this->manejarError($e);
            throw $e; // Re-lanzar para que el sistema de reintentos maneje
        }
    }

    /**
     * Normalizar número de teléfono para WhatsApp
     */
    protected function normalizarTelefono(string $telefono): ?string
    {
        // Eliminar espacios y caracteres especiales
        $telefono = preg_replace('/[^0-9+]/', '', $telefono);

        // Si no empieza con +, agregar código de país de Colombia
        if (!str_starts_with($telefono, '+')) {
            // Si empieza con 57, agregar solo el +
            if (str_starts_with($telefono, '57')) {
                $telefono = '+' . $telefono;
            } else {
                // Agregar +57 para Colombia
                $telefono = '+57' . $telefono;
            }
        }

        // Validar longitud mínima
        if (strlen($telefono) < 10) {
            return null;
        }

        return $telefono;
    }

    /**
     * Agregar tracking a mensaje de WhatsApp
     */
    protected function agregarTrackingWhatsApp(string $mensaje): string
    {
        // Generar URL corta de tracking
        $trackingUrl = route('campanas.tracking.whatsapp', [
            'trackingId' => $this->envio->tracking_id,
        ]);

        // Agregar footer con link de tracking (opcional)
        $footer = "\n\n_Este mensaje fue enviado por " . config('app.name') . "_";
        
        // Si hay URLs en el mensaje, reemplazarlas con URLs de tracking
        $mensaje = $this->reemplazarUrlsConTracking($mensaje);

        return $mensaje . $footer;
    }

    /**
     * Reemplazar URLs en el mensaje con URLs de tracking
     */
    protected function reemplazarUrlsConTracking(string $mensaje): string
    {
        // Patrón para detectar URLs
        $pattern = '/(https?:\/\/[^\s]+)/i';
        
        return preg_replace_callback($pattern, function ($matches) {
            $originalUrl = $matches[1];
            
            // Generar URL de tracking
            $trackingUrl = route('campanas.tracking.click', [
                'trackingId' => $this->envio->tracking_id,
                'url' => base64_encode($originalUrl),
            ]);
            
            // Opcional: acortar URL con servicio externo
            // $trackingUrl = $this->acortarUrl($trackingUrl);
            
            return $trackingUrl;
        }, $mensaje);
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
        Log::error("Error enviando WhatsApp de campaña", [
            'envio_id' => $this->envio->id,
            'campana_id' => $this->envio->campana_id,
            'user_id' => $this->envio->user_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Si es el último intento, marcar como fallido
        if ($this->attempts() >= $this->tries) {
            $this->marcarComoFallido($e->getMessage());
        } else {
            // Actualizar metadata para reintento
            $this->envio->update([
                'metadata' => array_merge($this->envio->metadata ?? [], [
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
        Log::error("Fallo definitivo enviando WhatsApp de campaña", [
            'envio_id' => $this->envio->id,
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

        // No reintentar si el número es inválido
        if (str_contains($exception->getMessage(), 'número') && 
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
        return [
            'campana:' . $this->envio->campana_id,
            'envio:' . $this->envio->id,
            'tipo:whatsapp',
        ];
    }
}