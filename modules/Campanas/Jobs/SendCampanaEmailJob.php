<?php

namespace Modules\Campanas\Jobs;

use Modules\Campanas\Models\CampanaEnvio;
use Modules\Campanas\Mail\CampanaEmail;
use Modules\Core\Jobs\Middleware\WithRateLimiting;
use Modules\Core\Jobs\Middleware\RateLimited;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCampanaEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WithRateLimiting;

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
        $this->onQueue(config('campanas.queues.email', 'otp-emails'));
    }

    /**
     * Obtener middleware del job
     */
    public function middleware(): array
    {
        return [
            new RateLimited('email'),
        ];
    }

    /**
     * Ejecutar el job
     */
    public function handle(): void
    {
        try {
            // Verificar que el envío esté en estado correcto
            if (!in_array($this->envio->estado, ['pendiente', 'enviando'])) {
                Log::warning("Envío {$this->envio->id} no está en estado válido para enviar: {$this->envio->estado}");
                return;
            }

            // Cargar relaciones necesarias
            $this->envio->load(['campana.plantillaEmail', 'user']);

            // Verificar que tengamos plantilla de email
            if (!$this->envio->campana->plantillaEmail) {
                throw new \Exception("No hay plantilla de email configurada para la campaña");
            }

            // Verificar que el usuario tenga email
            if (!$this->envio->user->email) {
                $this->marcarComoFallido("Usuario sin email configurado");
                return;
            }

            // Procesar contenido con variables del usuario
            $contenidoHtml = $this->envio->campana->plantillaEmail->procesarContenido($this->envio->user);
            $asunto = $this->envio->campana->plantillaEmail->procesarAsunto($this->envio->user);

            // Agregar tracking al contenido HTML
            if ($this->envio->campana->configuracion['tracking_enabled'] ?? true) {
                $contenidoHtml = $this->envio->procesarHtmlConTracking($contenidoHtml);
            }

            // Crear y enviar el email
            $email = new CampanaEmail(
                $this->envio,
                $asunto,
                $contenidoHtml,
                $this->envio->campana->plantillaEmail->contenido_texto
            );

            Mail::to($this->envio->user->email)
                ->send($email);

            // Marcar como enviado
            $this->envio->update([
                'estado' => 'enviado',
                'fecha_enviado' => now(),
            ]);

            // Actualizar métricas
            $this->actualizarMetricas();

            Log::info("Email enviado exitosamente", [
                'envio_id' => $this->envio->id,
                'campana_id' => $this->envio->campana_id,
                'user_id' => $this->envio->user_id,
                'email' => $this->envio->destinatario,
            ]);

        } catch (\Exception $e) {
            $this->manejarError($e);
            throw $e; // Re-lanzar para que el sistema de reintentos maneje
        }
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
            $metrica->increment('emails_rebotados');
        } else {
            $metrica->increment('total_enviados');
            $metrica->increment('emails_enviados');
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
        Log::error("Error enviando email de campaña", [
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
            // Actualizar estado para reintento
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
        Log::error("Fallo definitivo enviando email de campaña", [
            'envio_id' => $this->envio->id,
            'error' => $exception->getMessage(),
        ]);

        $this->marcarComoFallido("Fallo definitivo: " . $exception->getMessage());
    }

    /**
     * Determinar si el job debe ser liberado después de una excepción
     */
    public function shouldRetry(\Throwable $exception): bool
    {
        // No reintentar si es un error de validación o configuración
        if ($exception instanceof \InvalidArgumentException) {
            return false;
        }

        // No reintentar si el email es inválido
        if (str_contains($exception->getMessage(), 'email') && 
            str_contains($exception->getMessage(), 'invalid')) {
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
            'tipo:email',
        ];
    }
}