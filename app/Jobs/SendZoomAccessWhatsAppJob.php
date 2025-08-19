<?php

namespace App\Jobs;

use App\Jobs\Middleware\WithRateLimiting;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendZoomAccessWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WithRateLimiting;

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
    public $timeout = 30;
    
    /**
     * Determinar si el job debería marcarse como fallido en caso de excepción
     *
     * @var bool
     */
    public $failOnTimeout = true;

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
        // Inicializar la cola dedicada para WhatsApp (compartida con OTP)
        $this->initializeRateLimitedQueue();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = $this->notificationData['user'];
            $phone = $user->telefono;

            // Verificar si el número es obviamente inválido antes de intentar
            if (!$this->isPhoneFormatValid($phone)) {
                Log::warning("Número de teléfono con formato inválido para notificación Zoom", [
                    'phone' => $phone,
                    'user_id' => $user->id,
                    'length' => strlen($phone)
                ]);
                
                // No reintentar para números con formato inválido
                $this->fail(new \Exception("Formato de número de teléfono inválido: {$phone}"));
                return;
            }
            
            // Crear instancia del servicio WhatsApp
            $whatsappService = new WhatsAppService();
            
            // Generar mensaje con plantilla personalizada para Zoom
            $message = $this->getZoomWhatsAppMessageTemplate();
            
            // Enviar mensaje por WhatsApp
            $sent = $whatsappService->sendMessage($phone, $message);
            
            if ($sent) {
                Log::info("Notificación Zoom enviada exitosamente por WhatsApp mediante job", [
                    'phone' => $phone,
                    'user_id' => $user->id,
                    'asamblea_id' => $this->notificationData['asamblea']->id,
                    'zoom_registrant_id' => $this->notificationData['zoom_registrant']->id,
                    'attempt' => $this->attempts()
                ]);
            } else {
                // El servicio retornó false - verificar si es error permanente
                $this->handleSendFailure();
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Generar mensaje personalizado para notificaciones Zoom
     */
    private function getZoomWhatsAppMessageTemplate(): string
    {
        $user = $this->notificationData['user'];
        $asamblea = $this->notificationData['asamblea'];
        $maskedUrl = $this->notificationData['masked_url'];
        $zoomRegistrantId = $this->notificationData['zoom_registrant_id'];

        $fechaInicio = $asamblea->fecha_inicio->format('d/m/Y H:i');
        $fechaFin = $asamblea->fecha_fin->format('d/m/Y H:i');

        return "Hola {$user->name},\n\n" .
               "¡Tu acceso a la videoconferencia está listo!\n\n" .
               "📅 *Asamblea:* {$asamblea->nombre}\n" .
               "🕐 *Inicio:* {$fechaInicio}\n" .
               "🕐 *Fin:* {$fechaFin}\n\n" .
               "🔗 *Enlace de acceso:*\n{$maskedUrl}\n\n" .
               "🎫 *Tu código de registro:* {$zoomRegistrantId}\n\n" .
               "⚠️ *IMPORTANTE:*\n" .
               "• No compartas este enlace con nadie\n" .
               "• Solo un dispositivo puede conectarse por enlace\n" .
               "• El enlace estará disponible 15 minutos antes del inicio\n\n" .
               "Si tienes problemas técnicos, contacta al administrador.\n\n" .
               "¡Nos vemos en la asamblea! 👥";
    }

    /**
     * Manejar cuando el servicio retorna false
     */
    protected function handleSendFailure(): void
    {
        $errorMessage = "No se pudo enviar la notificación Zoom por WhatsApp";
        
        // Revisar si el error es permanente basado en el contexto
        if ($this->isPermanentError()) {
            Log::warning("Error permanente detectado en notificación Zoom, no se reintentará", [
                'phone' => $this->notificationData['user']->telefono,
                'user_id' => $this->notificationData['user']->id,
                'attempt' => $this->attempts()
            ]);
            
            $this->fail(new \Exception($errorMessage));
            return;
        }
        
        // Para errores temporales, lanzar excepción para reintentar
        throw new \Exception($errorMessage);
    }
    
    /**
     * Manejar excepciones del job
     */
    protected function handleException(\Exception $e): void
    {
        $message = $e->getMessage();
        
        // Lista de errores que NO deben reintentarse (errores permanentes)
        $permanentErrors = [
            'exists":false',
            'number not found',
            'Número de teléfono inválido',
            'Invalid phone number',
            'Bad Request',
            'Formato de número',
            'Usuario bloqueó',
            'Unauthorized',
        ];
        
        // Verificar si es un error permanente
        $isPermanent = false;
        foreach ($permanentErrors as $errorPattern) {
            if (stripos($message, $errorPattern) !== false) {
                $isPermanent = true;
                break;
            }
        }
        
        if ($isPermanent) {
            // Error permanente - no reintentar
            Log::warning("Error permanente de WhatsApp en notificación Zoom, no se reintentará", [
                'phone' => $this->notificationData['user']->telefono,
                'user_id' => $this->notificationData['user']->id,
                'error' => $message,
                'attempt' => $this->attempts()
            ]);
            
            $this->fail($e);
            return;
        }
        
        // Para errores temporales
        Log::error("Error temporal enviando notificación Zoom por WhatsApp", [
            'phone' => $this->notificationData['user']->telefono,
            'user_id' => $this->notificationData['user']->id,
            'asamblea_id' => $this->notificationData['asamblea']->id,
            'error' => $message,
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries,
            'will_retry' => $this->attempts() < $this->tries
        ]);
        
        // Re-lanzar la excepción para reintentos
        throw $e;
    }
    
    /**
     * Validación básica del formato del número
     */
    protected function isPhoneFormatValid(string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }

        // Remover espacios y caracteres especiales
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Verificar longitud mínima y máxima razonable
        $length = strlen($cleanPhone);
        
        if ($length < 7 || $length > 15) {
            return false;
        }
        
        // Verificar que no sea un número obviamente falso
        if (preg_match('/^(\d)\1+$/', $cleanPhone)) {
            return false;
        }
        
        if (preg_match('/^1234567/', $cleanPhone)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Determinar si el error actual es permanente
     */
    protected function isPermanentError(): bool
    {
        // Si ya hemos intentado 2 veces y sigue fallando,
        // probablemente es un número inválido
        if ($this->attempts() >= 2) {
            return true;
        }
        
        return false;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de notificación Zoom por WhatsApp falló definitivamente", [
            'phone' => $this->notificationData['user']->telefono ?? 'N/A',
            'user_id' => $this->notificationData['user']->id ?? 'N/A',
            'asamblea_id' => $this->notificationData['asamblea']->id ?? 'N/A',
            'error' => $exception->getMessage(),
            'total_attempts' => $this->attempts(),
        ]);
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
