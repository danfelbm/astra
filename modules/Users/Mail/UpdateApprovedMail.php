<?php

namespace Modules\Users\Mail;

use Modules\Users\Models\UserUpdateRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UpdateApprovedMail extends Mailable
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
            subject: '✅ Tu actualización de datos ha sido aprobada',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'users::emails.user-update-approved',
            with: [
                'updateRequest' => $this->updateRequest,
                'user' => $this->updateRequest->user,
                'changes' => $this->updateRequest->getChangesSummary(),
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
