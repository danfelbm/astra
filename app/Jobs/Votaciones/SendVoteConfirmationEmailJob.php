<?php

namespace App\Jobs\Votaciones;

use App\Jobs\Middleware\WithRateLimiting;
use App\Mail\Votaciones\VoteConfirmationMail;
use App\Models\Core\User;
use App\Models\Votaciones\Votacion;
use App\Models\Votaciones\Voto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendVoteConfirmationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WithRateLimiting;

    /**
     * El número de veces que el job puede ser intentado.
     */
    public $tries = 3;

    /**
     * El número de segundos para esperar antes de reintentar el job.
     */
    public $backoff = 10;

    /**
     * El tiempo máximo que el job puede ejecutarse antes de timeout
     */
    public $timeout = 30;

    protected User $user;
    protected Votacion $votacion;
    protected Voto $voto;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, Votacion $votacion, Voto $voto)
    {
        $this->user = $user;
        $this->votacion = $votacion;
        $this->voto = $voto;
        
        // Inicializar la cola dedicada para emails con rate limiting
        $this->initializeRateLimitedQueue();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Verificar que el usuario tenga email
            if (empty($this->user->email)) {
                Log::warning('Usuario sin email, no se puede enviar confirmación de voto', [
                    'user_id' => $this->user->id,
                    'voto_id' => $this->voto->id
                ]);
                return;
            }

            // Enviar el email con los datos del voto
            Mail::to($this->user->email)
                ->send(new VoteConfirmationMail(
                    $this->user,
                    $this->votacion,
                    $this->voto
                ));

            Log::info('Email de confirmación de voto enviado', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'votacion_id' => $this->votacion->id,
                'voto_id' => $this->voto->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando email de confirmación de voto', [
                'user_id' => $this->user->id,
                'voto_id' => $this->voto->id,
                'error' => $e->getMessage()
            ]);
            
            // Re-lanzar la excepción para que Laravel maneje los reintentos
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de email de confirmación de voto falló definitivamente', [
            'user_id' => $this->user->id,
            'voto_id' => $this->voto->id,
            'error' => $exception->getMessage()
        ]);
    }
}