<?php

namespace App\Mail\Elecciones;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidaturaRechazadaMail extends Mailable
{
    use SerializesModels;

    public string $userName;
    public int $candidaturaId;
    public string $comentarios;
    public string $platformUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $userName, int $candidaturaId, string $comentarios)
    {
        $this->userName = $userName;
        $this->candidaturaId = $candidaturaId;
        $this->comentarios = $comentarios; // Ahora en HTML desde el editor WYSIWYG
        $this->platformUrl = config('app.url');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📝 Tu candidatura requiere ajustes',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.candidatura-rechazada',
            with: [
                'userName' => $this->userName,
                'candidaturaId' => $this->candidaturaId,
                'comentarios' => $this->comentarios,
                'platformUrl' => $this->platformUrl,
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