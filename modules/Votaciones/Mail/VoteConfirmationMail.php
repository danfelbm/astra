<?php

namespace Modules\Votaciones\Mail;

use Modules\Core\Models\User;
use Modules\Votaciones\Models\Votacion;
use Modules\Votaciones\Models\Voto;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoteConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Votacion $votacion;
    public Voto $voto;
    public string $verificationUrl;
    public string $voteDateTime;
    public string $votacionTitulo;
    public string $votacionCategoria;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Votacion $votacion, Voto $voto)
    {
        $this->user = $user;
        $this->votacion = $votacion;
        $this->voto = $voto;
        
        // Generar URL de verificación
        $this->verificationUrl = url('/verificar-token/' . $voto->token_unico);
        
        // Formatear fecha y hora del voto con zona horaria de la votación
        $this->voteDateTime = Carbon::parse($voto->created_at)
            ->setTimezone($votacion->timezone)
            ->format('d/m/Y H:i') . ' (' . $this->getTimezoneAbbreviation($votacion->timezone) . ')';
        
        // Preparar datos de la votación
        $this->votacionTitulo = $votacion->titulo;
        $this->votacionCategoria = $votacion->categoria ? $votacion->categoria->nombre : 'General';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Confirmación de Voto - ' . $this->votacionTitulo,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.vote-confirmation',
            with: [
                'userName' => $this->user->name,
                'votacionTitulo' => $this->votacionTitulo,
                'votacionCategoria' => $this->votacionCategoria,
                'token' => $this->voto->token_unico,
                'voteDateTime' => $this->voteDateTime,
                'verificationUrl' => $this->verificationUrl,
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

    /**
     * Obtener abreviación de zona horaria
     */
    protected function getTimezoneAbbreviation(string $timezone): string
    {
        $abbreviations = [
            'America/Bogota' => 'GMT-5',
            'America/Mexico_City' => 'GMT-6',
            'America/Lima' => 'GMT-5',
            'America/Argentina/Buenos_Aires' => 'GMT-3',
            'America/Santiago' => 'GMT-3',
            'America/Caracas' => 'GMT-4',
            'America/La_Paz' => 'GMT-4',
            'America/Guayaquil' => 'GMT-5',
            'America/Asuncion' => 'GMT-3',
            'America/Montevideo' => 'GMT-3',
            'America/Panama' => 'GMT-5',
            'America/Costa_Rica' => 'GMT-6',
            'America/Guatemala' => 'GMT-6',
            'America/Havana' => 'GMT-5',
            'America/Santo_Domingo' => 'GMT-4',
        ];

        return $abbreviations[$timezone] ?? 'GMT';
    }
}