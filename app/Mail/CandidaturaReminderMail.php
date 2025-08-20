<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidaturaReminderMail extends Mailable
{
    use SerializesModels;

    public string $userName;
    public int $candidaturaId;
    public string $platformUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $userName, int $candidaturaId)
    {
        $this->userName = $userName;
        $this->candidaturaId = $candidaturaId;
        $this->platformUrl = config('app.url');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio: Completa tu candidatura en borrador',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.candidatura-reminder',
            with: [
                'userName' => $this->userName,
                'candidaturaId' => $this->candidaturaId,
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