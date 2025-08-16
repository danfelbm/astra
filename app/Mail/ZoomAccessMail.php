<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ZoomAccessMail extends Mailable
{
    use SerializesModels;

    public string $userName;
    public string $asambleaNombre;
    public string $maskedUrl;
    public string $zoomRegistrantId;
    public Carbon $fechaInicio;
    public Carbon $fechaFin;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $userName,
        string $asambleaNombre,
        string $maskedUrl,
        string $zoomRegistrantId,
        Carbon $fechaInicio,
        Carbon $fechaFin
    ) {
        $this->userName = $userName;
        $this->asambleaNombre = $asambleaNombre;
        $this->maskedUrl = $maskedUrl;
        $this->zoomRegistrantId = $zoomRegistrantId;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🎥 Tu acceso a la videoconferencia: {$this->asambleaNombre}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.zoom-access',
            with: [
                'userName' => $this->userName,
                'asambleaNombre' => $this->asambleaNombre,
                'maskedUrl' => $this->maskedUrl,
                'zoomRegistrantId' => $this->zoomRegistrantId,
                'fechaInicio' => $this->fechaInicio,
                'fechaFin' => $this->fechaFin,
                'fechaInicioFormateada' => $this->fechaInicio->format('d/m/Y H:i'),
                'fechaFinFormateada' => $this->fechaFin->format('d/m/Y H:i'),
                'fechaInicioCompleta' => $this->fechaInicio->format('l, d \d\e F \d\e Y \a \l\a\s H:i'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
