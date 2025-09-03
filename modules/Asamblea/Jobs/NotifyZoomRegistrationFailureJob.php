<?php

namespace Modules\Asamblea\Jobs;

use Modules\Asamblea\Models\ZoomRegistrant;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyZoomRegistrationFailureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * El número de veces que el job puede ser intentado.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * El tiempo máximo que el job puede ejecutarse antes de timeout
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * ID del ZoomRegistrant que falló
     */
    public int $zoomRegistrantId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $zoomRegistrantId)
    {
        $this->zoomRegistrantId = $zoomRegistrantId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $registration = ZoomRegistrant::with(['user', 'asamblea'])->find($this->zoomRegistrantId);

            if (!$registration) {
                Log::warning('ZoomRegistrant no encontrado para notificación de fallo', ['id' => $this->zoomRegistrantId]);
                return;
            }

            if (!$registration->isFailed()) {
                Log::info('ZoomRegistrant no está en estado failed, saltando notificación', ['id' => $this->zoomRegistrantId]);
                return;
            }

            // Enviar email simple al usuario informando del problema
            $user = $registration->user;
            $asamblea = $registration->asamblea;

            if ($user && $user->email) {
                Mail::raw(
                    $this->buildFailureMessage($user->name, $asamblea->nombre, $registration->error_message),
                    function ($message) use ($user, $asamblea) {
                        $message->to($user->email)
                               ->subject("⚠️ Problema con el registro de videoconferencia: {$asamblea->nombre}");
                    }
                );

                Log::info('Notificación de fallo de registro Zoom enviada', [
                    'zoom_registrant_id' => $registration->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'asamblea_id' => $asamblea->id
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error enviando notificación de fallo de registro Zoom', [
                'zoom_registrant_id' => $this->zoomRegistrantId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            throw $e;
        }
    }

    /**
     * Construir mensaje de fallo
     */
    private function buildFailureMessage(string $userName, string $asambleaNombre, ?string $errorMessage): string
    {
        $message = "Hola {$userName},\n\n";
        $message .= "Lamentamos informarte que ha ocurrido un problema técnico al registrarte para la videoconferencia de la asamblea \"{$asambleaNombre}\".\n\n";
        $message .= "Error técnico: " . ($errorMessage ?? 'Error desconocido') . "\n\n";
        $message .= "Por favor, intenta registrarte nuevamente o contacta al administrador si el problema persiste.\n\n";
        $message .= "Disculpas por las molestias.\n\n";
        $message .= "Equipo de Votaciones Digitales";

        return $message;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de notificación de fallo Zoom falló definitivamente', [
            'zoom_registrant_id' => $this->zoomRegistrantId,
            'error' => $exception->getMessage(),
            'total_attempts' => $this->tries,
        ]);
    }
}
