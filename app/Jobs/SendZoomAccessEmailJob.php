<?php

namespace App\Jobs;

use App\Mail\ZoomAccessMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendZoomAccessEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * El número de veces que el job puede ser intentado.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * El número de segundos para esperar antes de reintentar el job.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * El tiempo máximo que el job puede ejecutarse antes de timeout
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Datos de la notificación
     */
    public array $notificationData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $notificationData)
    {
        $this->notificationData = $notificationData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = $this->notificationData['user'];
            $asamblea = $this->notificationData['asamblea'];
            $maskedUrl = $this->notificationData['masked_url'];
            $zoomRegistrantId = $this->notificationData['zoom_registrant_id'];

            // Crear y enviar el email
            $mail = new ZoomAccessMail(
                $user->name,
                $asamblea->nombre,
                $maskedUrl,
                $zoomRegistrantId,
                $asamblea->fecha_inicio,
                $asamblea->fecha_fin
            );
            
            Mail::to($user->email)->send($mail);
            
            Log::info("Notificación Zoom enviada exitosamente por email mediante job", [
                'email' => $user->email,
                'asamblea_id' => $asamblea->id,
                'zoom_registrant_id' => $this->notificationData['zoom_registrant']->id,
                'attempt' => $this->attempts()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error enviando notificación Zoom por email mediante job", [
                'email' => $this->notificationData['user']->email ?? 'N/A',
                'asamblea_id' => $this->notificationData['asamblea']->id ?? 'N/A',
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);
            
            // Re-lanzar la excepción para que el job se reintente
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de notificación Zoom por email falló definitivamente", [
            'email' => $this->notificationData['user']->email ?? 'N/A',
            'asamblea_id' => $this->notificationData['asamblea']->id ?? 'N/A',
            'error' => $exception->getMessage(),
            'total_attempts' => $this->attempts(),
        ]);
        
        // Aquí se podría enviar una notificación al administrador
        // o registrar en una tabla de notificaciones fallidas
    }

    /**
     * Calcular el tiempo de espera antes del siguiente reintento
     */
    public function backoff(): array
    {
        // Backoff exponencial: 10s, 30s, 60s
        return [10, 30, 60];
    }
}
