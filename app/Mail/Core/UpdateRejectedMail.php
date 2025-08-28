<?php

namespace App\Mail\Core;

use App\Models\Core\UserUpdateRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UpdateRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public UserUpdateRequest $updateRequest;

    /**
     * Create a new message instance.
     */
    public function __construct(UserUpdateRequest $updateRequest)
    {
        $this->updateRequest = $updateRequest;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❌ Tu solicitud de actualización ha sido rechazada',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user-update-rejected',
            with: [
                'updateRequest' => $this->updateRequest,
                'user' => $this->updateRequest->user,
                'adminNotes' => $this->updateRequest->admin_notes,
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
