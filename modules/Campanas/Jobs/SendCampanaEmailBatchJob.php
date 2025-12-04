<?php

namespace Modules\Campanas\Jobs;

use Modules\Campanas\Models\Campana;
use Modules\Campanas\Models\CampanaEnvio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

/**
 * Job para enviar emails en batch usando la API de Resend
 * Permite enviar hasta 100 emails en un solo request HTTP
 */
class SendCampanaEmailBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tries = 3;
    protected $timeout = 120; // 2 minutos para procesar batch
    public $maxExceptions = 2;
    public $backoff = [10, 30, 60];

    /**
     * IDs de los envíos a procesar (serializamos IDs en lugar de modelos)
     */
    protected array $envioIds;
    protected int $campanaId;

    /**
     * Constructor
     */
    public function __construct(Collection $envios, Campana $campana)
    {
        $this->envioIds = $envios->pluck('id')->toArray();
        $this->campanaId = $campana->id;
        $this->onQueue(config('campanas.queues.email', 'otp-emails'));
    }

    /**
     * Verificar si está en modo LOG (desarrollo/debug)
     */
    protected function isLogMode(): bool
    {
        return config('mail.default') === 'log';
    }

    /**
     * Ejecutar el job
     */
    public function handle(): void
    {
        // Cargar modelos frescos desde la DB
        $campana = Campana::with('plantillaEmail')->find($this->campanaId);

        if (!$campana || !$campana->plantillaEmail) {
            Log::error("Campaña {$this->campanaId} no encontrada o sin plantilla de email");
            return;
        }

        $envios = CampanaEnvio::with('user')
            ->whereIn('id', $this->envioIds)
            ->whereIn('estado', ['pendiente', 'enviando'])
            ->get();

        if ($envios->isEmpty()) {
            Log::info("No hay envíos pendientes para procesar en batch");
            return;
        }

        $modoLog = $this->isLogMode();
        $modoTexto = $modoLog ? ' [MODO LOG]' : '';

        Log::info("Procesando batch de {$envios->count()} emails para campaña {$this->campanaId}{$modoTexto}");

        try {
            // Preparar array de emails para Resend batch
            $emails = [];
            $envioMap = []; // Mapear índice a envío para procesar respuesta

            foreach ($envios as $index => $envio) {
                // Verificar que el usuario tenga email
                if (!$envio->user || !$envio->user->email) {
                    $envio->marcarFallido('Usuario sin email configurado');
                    continue;
                }

                // Procesar contenido con variables del usuario
                $contenidoHtml = $campana->plantillaEmail->procesarContenido($envio->user);
                $asunto = $campana->plantillaEmail->procesarAsunto($envio->user);

                // Agregar tracking al contenido HTML
                $trackingEnabled = $campana->configuracion['tracking_enabled'] ??
                                   $campana->configuracion['enable_tracking'] ?? true;

                if ($trackingEnabled) {
                    $contenidoHtml = $envio->procesarHtmlConTracking($contenidoHtml);
                }

                // Preparar email para batch
                $emailData = [
                    'from' => $this->getFromAddress($campana),
                    'to' => [$envio->user->email],
                    'subject' => $asunto,
                    'html' => $contenidoHtml,
                ];

                // Agregar texto plano si existe
                if ($campana->plantillaEmail->contenido_texto) {
                    $emailData['text'] = $campana->plantillaEmail->procesarContenidoTexto($envio->user);
                }

                $emails[] = $emailData;
                $envioMap[count($emails) - 1] = $envio;
            }

            if (empty($emails)) {
                Log::warning("No hay emails válidos para enviar en batch");
                return;
            }

            // Si está en modo LOG, simular envío sin llamar a Resend API
            if ($modoLog) {
                $this->simularEnvioModoLog($emails, $envioMap, $campana);
                return;
            }

            // En modo producción, enviar batch via Resend API con modo permissive
            $response = Resend::batch()->send($emails, [
                'batch_validation' => 'permissive',
            ]);

            // Procesar respuesta
            $this->procesarRespuesta($response, $envioMap, $campana);

        } catch (\Exception $e) {
            Log::error("Error en batch de emails para campaña {$this->campanaId}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            // Marcar todos como fallidos si el batch completo falla
            foreach ($envios as $envio) {
                if ($envio->estado === 'enviando') {
                    $envio->marcarFallido("Error en batch: {$e->getMessage()}");
                }
            }

            throw $e;
        }
    }

    /**
     * Obtener dirección de remitente
     */
    protected function getFromAddress(Campana $campana): string
    {
        $fromName = config('mail.from.name', config('app.name'));
        $fromAddress = config('mail.from.address');

        return "{$fromName} <{$fromAddress}>";
    }

    /**
     * Simular envío en modo LOG (desarrollo/debug)
     * No llama a la API de Resend, solo actualiza estados
     * Solo registra resumen del batch, no cada email individual
     */
    protected function simularEnvioModoLog(array $emails, array $envioMap, Campana $campana): void
    {
        $exitosos = 0;

        foreach ($envioMap as $index => $envio) {
            $emailData = $emails[$index] ?? null;

            if (!$emailData) {
                continue;
            }

            // Generar ID simulado
            $fakeResendId = 'LOG_' . uniqid() . '_' . $index;

            // Marcar como enviado (simulado) - sin log individual
            $envio->update([
                'estado' => 'enviado',
                'fecha_enviado' => now(),
                'metadata' => array_merge($envio->metadata ?? [], [
                    'resend_id' => $fakeResendId,
                    'batch_index' => $index,
                    'enviado_via' => 'log_mode',
                    'modo_debug' => true,
                ]),
            ]);

            $exitosos++;
        }

        // Actualizar métricas
        $this->actualizarMetricas($campana, $exitosos, 0);

        // Solo registrar resumen del batch
        Log::info("Batch de emails procesado (modo LOG) para campaña {$this->campanaId}", [
            'exitosos' => $exitosos,
            'total' => count($emails),
        ]);
    }

    /**
     * Procesar respuesta del batch de Resend
     * Solo registra resumen y errores, no cada email exitoso
     */
    protected function procesarRespuesta($response, array $envioMap, Campana $campana): void
    {
        $exitosos = 0;
        $fallidos = 0;
        $erroresDetalle = [];

        // Procesar emails enviados exitosamente - sin log individual
        if (isset($response->data) && is_array($response->data)) {
            foreach ($response->data as $index => $result) {
                if (!isset($envioMap[$index])) {
                    continue;
                }

                $envio = $envioMap[$index];

                if (isset($result->id)) {
                    $envio->update([
                        'estado' => 'enviado',
                        'fecha_enviado' => now(),
                        'metadata' => array_merge($envio->metadata ?? [], [
                            'resend_id' => $result->id,
                            'batch_index' => $index,
                            'enviado_via' => 'resend_batch',
                        ]),
                    ]);
                    $exitosos++;
                }
            }
        }

        // Procesar errores (modo permissive) - estos SÍ se registran
        if (isset($response->errors) && is_array($response->errors)) {
            foreach ($response->errors as $error) {
                $index = $error->index ?? null;

                if ($index !== null && isset($envioMap[$index])) {
                    $envio = $envioMap[$index];
                    $mensaje = $error->message ?? 'Error desconocido en Resend';

                    $envio->update([
                        'estado' => 'fallido',
                        'error_mensaje' => $mensaje,
                        'metadata' => array_merge($envio->metadata ?? [], [
                            'resend_error' => $mensaje,
                            'batch_index' => $index,
                        ]),
                    ]);

                    // Guardar detalle del error para log
                    $erroresDetalle[] = [
                        'envio_id' => $envio->id,
                        'to' => $envio->destinatario,
                        'error' => $mensaje,
                    ];
                    $fallidos++;
                }
            }
        }

        // Actualizar métricas de la campaña
        $this->actualizarMetricas($campana, $exitosos, $fallidos);

        // Solo registrar resumen del batch
        Log::info("Batch de emails procesado para campaña {$this->campanaId}", [
            'exitosos' => $exitosos,
            'fallidos' => $fallidos,
            'total' => count($envioMap),
        ]);

        // Registrar errores individuales como ERROR (importante para debugging)
        if (!empty($erroresDetalle)) {
            Log::error("Errores en batch de emails para campaña {$this->campanaId}", [
                'errores' => $erroresDetalle,
            ]);
        }
    }

    /**
     * Actualizar métricas de la campaña
     */
    protected function actualizarMetricas(Campana $campana, int $exitosos, int $fallidos): void
    {
        $metrica = $campana->metrica;

        if (!$metrica) {
            return;
        }

        if ($exitosos > 0) {
            $metrica->increment('total_enviados', $exitosos);
            $metrica->increment('emails_enviados', $exitosos);
        }

        if ($fallidos > 0) {
            $metrica->increment('total_fallidos', $fallidos);
            $metrica->increment('emails_rebotados', $fallidos);
        }

        // Actualizar total pendientes
        $pendientes = $campana->envios()
            ->where('estado', 'pendiente')
            ->count();

        $metrica->update([
            'total_pendientes' => $pendientes,
            'ultima_actualizacion' => now(),
        ]);
    }

    /**
     * Manejar fallo definitivo del job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Fallo definitivo en batch de emails para campaña {$this->campanaId}", [
            'error' => $exception->getMessage(),
            'envios' => $this->envioIds,
        ]);

        // Marcar envíos que quedaron en 'enviando' como fallidos
        CampanaEnvio::whereIn('id', $this->envioIds)
            ->where('estado', 'enviando')
            ->update([
                'estado' => 'fallido',
                'error_mensaje' => "Fallo en batch: {$exception->getMessage()}",
            ]);
    }

    /**
     * Obtener tags para logging
     */
    public function tags(): array
    {
        return [
            'campana:' . $this->campanaId,
            'tipo:email_batch',
            'count:' . count($this->envioIds),
        ];
    }
}
