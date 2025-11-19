<?php

namespace Modules\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $code;
    public string $channel;

    /**
     * Create a new message instance.
     */
    public function __construct(string $userName, string $code, string $channel = 'email')
    {
        $this->userName = $userName;
        $this->code = $code;
        $this->channel = $channel;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de Verificación - Sistema de Votaciones',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'core::emails.user-verification',
            with: [
                'userName' => $this->userName,
                'code' => $this->code,
                'channel' => $this->channel,
            ]
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
