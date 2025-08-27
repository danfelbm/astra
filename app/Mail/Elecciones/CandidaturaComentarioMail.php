<?php

namespace App\Mail\Elecciones;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidaturaComentarioMail extends Mailable
{
    use SerializesModels;

    public string $userName;
    public int $candidaturaId;
    public string $comentario;
    public string $platformUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $userName, int $candidaturaId, string $comentario)
    {
        $this->userName = $userName;
        $this->candidaturaId = $candidaturaId;
        $this->comentario = $comentario;
        $this->platformUrl = config('app.url');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💬 Nuevo comentario en tu candidatura',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.candidatura-comentario',
            with: [
                'userName' => $this->userName,
                'candidaturaId' => $this->candidaturaId,
                'comentario' => $this->comentario,
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