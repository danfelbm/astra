<?php

namespace App\Jobs\Votaciones;

use App\Jobs\Middleware\WithRateLimiting;
use App\Models\Core\User;
use App\Models\Votaciones\Votacion;
use App\Models\Votaciones\Voto;
use App\Services\Core\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendVoteConfirmationWhatsAppJob implements ShouldQueue
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
        
        // Inicializar la cola dedicada para WhatsApp
        $this->initializeRateLimitedQueue();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Verificar que el usuario tenga teléfono
            if (empty($this->user->telefono)) {
                Log::warning('Usuario sin teléfono, no se puede enviar WhatsApp de confirmación', [
                    'user_id' => $this->user->id,
                    'voto_id' => $this->voto->id
                ]);
                return;
            }

            // Verificar formato del teléfono antes de intentar
            if (!$this->isPhoneFormatValid($this->user->telefono)) {
                Log::warning('Formato de teléfono inválido para confirmación de voto', [
                    'phone' => $this->user->telefono,
                    'user_id' => $this->user->id,
                    'voto_id' => $this->voto->id
                ]);
                return;
            }

            // Crear instancia del servicio WhatsApp
            $whatsappService = new WhatsAppService();

            // Generar mensaje con plantilla
            $message = $whatsappService->getVoteConfirmationTemplate(
                $this->user->name,
                $this->votacion->titulo,
                $this->voto->token_unico,
                $this->voto->created_at,
                $this->votacion->timezone
            );

            // Enviar mensaje por WhatsApp
            $sent = $whatsappService->sendMessage($this->user->telefono, $message);

            if ($sent) {
                Log::info('WhatsApp de confirmación de voto enviado', [
                    'user_id' => $this->user->id,
                    'phone' => $this->user->telefono,
                    'votacion_id' => $this->votacion->id,
                    'voto_id' => $this->voto->id
                ]);
            } else {
                // El servicio retornó false - verificar si es error permanente
                $this->handleSendFailure();
            }

        } catch (\Exception $e) {
            Log::error('Error enviando WhatsApp de confirmación de voto', [
                'user_id' => $this->user->id,
                'voto_id' => $this->voto->id,
                'error' => $e->getMessage()
            ]);
            
            // Re-lanzar la excepción para que Laravel maneje los reintentos
            throw $e;
        }
    }

    /**
     * Verificar si el formato del teléfono es válido
     */
    protected function isPhoneFormatValid(string $phone): bool
    {
        // Eliminar espacios y caracteres especiales
        $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        // Verificar longitud mínima (al menos 7 dígitos)
        if (strlen($cleanPhone) < 7) {
            return false;
        }
        
        // Verificar que contenga solo números y opcionalmente un + al inicio
        if (!preg_match('/^\+?\d+$/', $cleanPhone)) {
            return false;
        }
        
        return true;
    }

    /**
     * Manejar falla de envío
     */
    protected function handleSendFailure(): void
    {
        Log::warning('WhatsApp de confirmación de voto no pudo ser enviado', [
            'user_id' => $this->user->id,
            'voto_id' => $this->voto->id,
            'phone' => $this->user->telefono,
            'attempt' => $this->attempts()
        ]);
        
        // Si es el último intento, registrar como falla definitiva
        if ($this->attempts() >= $this->tries) {
            Log::error('WhatsApp de confirmación de voto falló después de todos los intentos', [
                'user_id' => $this->user->id,
                'voto_id' => $this->voto->id
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de WhatsApp de confirmación de voto falló definitivamente', [
            'user_id' => $this->user->id,
            'voto_id' => $this->voto->id,
            'error' => $exception->getMessage()
        ]);
    }
}