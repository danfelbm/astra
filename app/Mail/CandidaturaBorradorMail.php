<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidaturaBorradorMail extends Mailable
{
    use SerializesModels;

    public string $userName;
    public int $candidaturaId;
    public ?string $motivo;
    public string $platformUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $userName, int $candidaturaId, ?string $motivo = null)
    {
        $this->userName = $userName;
        $this->candidaturaId = $candidaturaId;
        $this->motivo = $motivo; // Ahora en HTML desde el editor WYSIWYG
        $this->platformUrl = config('app.url');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '↩️ Tu candidatura ha sido devuelta a borrador',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.candidatura-borrador',
            with: [
                'userName' => $this->userName,
                'candidaturaId' => $this->candidaturaId,
                'motivo' => $this->motivo,
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