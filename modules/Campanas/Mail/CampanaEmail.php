<?php

namespace Modules\Campanas\Mail;

use Modules\Campanas\Models\CampanaEnvio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampanaEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Constructor
     */
    public function __construct(
        protected CampanaEnvio $envio,
        protected string $asunto,
        protected string $contenidoHtml,
        protected ?string $contenidoTexto = null
    ) {}

    /**
     * Obtener el sobre del mensaje
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->asunto,
            metadata: [
                'campana_id' => $this->envio->campana_id,
                'envio_id' => $this->envio->id,
                'tracking_id' => $this->envio->tracking_id,
            ],
        );
    }

    /**
     * Obtener la definición del contenido del mensaje
     */
    public function content(): Content
    {
        // Solo pasar text si es null o vacío para evitar conflictos
        return new Content(
            htmlString: $this->contenidoHtml,
            text: null, // Temporalmente deshabilitado el texto plano
        );
    }

    /**
     * Obtener los archivos adjuntos del mensaje
     */
    public function attachments(): array
    {
        $attachments = [];

        // Si la campaña tiene archivos adjuntos configurados
        $configuracion = $this->envio->campana->configuracion ?? [];
        
        if (!empty($configuracion['attachments'])) {
            foreach ($configuracion['attachments'] as $attachment) {
                if (file_exists($attachment['path'])) {
                    $attachments[] = [
                        'path' => $attachment['path'],
                        'as' => $attachment['name'] ?? basename($attachment['path']),
                        'mime' => $attachment['mime'] ?? null,
                    ];
                }
            }
        }

        return $attachments;
    }

    /**
     * Configurar headers y opciones adicionales
     */
    public function headers(): \Illuminate\Mail\Mailables\Headers
    {
        $messageId = sprintf(
            "%s.%s@%s",
            $this->envio->tracking_id,
            time(),
            parse_url(config('app.url'), PHP_URL_HOST)
        );

        return new \Illuminate\Mail\Mailables\Headers(
            messageId: $messageId,
            text: [
                'X-Campaign-ID' => $this->envio->campana_id,
                'X-Send-ID' => $this->envio->id,
                'X-Tracking-ID' => $this->envio->tracking_id,
                'List-Unsubscribe' => '<' . route('campanas.tracking.unsubscribe', ['trackingId' => $this->envio->tracking_id]) . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                'Auto-Submitted' => 'auto-generated',
                'Precedence' => 'bulk',
            ],
        );
    }
}