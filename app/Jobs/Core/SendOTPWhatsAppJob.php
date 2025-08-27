<?php

namespace App\Jobs\Core;

use App\Jobs\Middleware\WithRateLimiting;
use App\Services\Core\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOTPWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WithRateLimiting;

    /**
     * El número de veces que el job puede ser intentado.
     * IMPORTANTE: Este límite se respeta SIEMPRE por Laravel
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
     * Create a new job instance.
     */
    public function __construct(
        public string $phone,
        public string $codigo,
        public string $userName,
        public int $expirationMinutes = 10
    ) {
        // Inicializar la cola dedicada para OTP WhatsApp
        $this->initializeRateLimitedQueue();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Verificar si el número es obviamente inválido antes de intentar
            if (!$this->isPhoneFormatValid($this->phone)) {
                Log::warning("Número de teléfono con formato inválido, no se enviará OTP", [
                    'phone' => $this->phone,
                    'length' => strlen($this->phone)
                ]);
                
                // No reintentar para números con formato inválido
                $this->fail(new \Exception("Formato de número de teléfono inválido: {$this->phone}"));
                return;
            }
            
            // Crear instancia del servicio WhatsApp
            $whatsappService = new WhatsAppService();
            
            // Generar mensaje con plantilla
            $message = $whatsappService->getOTPMessageTemplate(
                $this->codigo,
                $this->userName,
                $this->expirationMinutes
            );
            
            // Enviar mensaje por WhatsApp
            $sent = $whatsappService->sendMessage($this->phone, $message);
            
            if ($sent) {
                Log::info("OTP enviado exitosamente");
            } else {
                // El servicio retornó false - verificar si es error permanente
                $this->handleSendFailure();
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Manejar cuando el servicio retorna false
     */
    protected function handleSendFailure(): void
    {
        // Buscar en los logs recientes si fue un número inválido
        // Este es un error permanente, no debe reintentarse
        $errorMessage = "No se pudo enviar el mensaje de WhatsApp";
        
        // Revisar si el error es permanente basado en el contexto
        // Los números inválidos no deben reintentarse
        if ($this->isPermanentError()) {
            Log::warning("Error permanente detectado, no se reintentará", [
                'phone' => $this->phone,
                'attempt' => $this->attempts()
            ]);
            
            $this->fail(new \Exception($errorMessage));
            return;
        }
        
        // Para errores temporales, loggear y marcar como fallido
        Log::error("No se pudo enviar el mensaje de WhatsApp", [
            'phone' => $this->phone,
            'attempt' => $this->attempts()
        ]);
        
        // Marcar como fallido sin generar stacktrace
        $this->fail(new \Exception($errorMessage));
    }
    
    /**
     * Manejar excepciones del job
     */
    protected function handleException(\Exception $e): void
    {
        $message = $e->getMessage();
        
        // Lista de errores que NO deben reintentarse (errores permanentes)
        $permanentErrors = [
            'exists":false',                    // Número no existe en WhatsApp
            'number not found',                 // Número no encontrado
            'Número de teléfono inválido',     // Validación fallida
            'Invalid phone number',             // Número inválido
            'Bad Request',                      // Error 400 - problema del cliente
            'Formato de número',                // Problemas de formato
            'Usuario bloqueó',                  // Usuario bloqueó el bot
            'Unauthorized',                     // Error 401 - credenciales inválidas
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
            Log::warning("Error permanente de WhatsApp, no se reintentará", [
                'phone' => $this->phone,
                'error' => $message,
                'attempt' => $this->attempts()
            ]);
            
            // Marcar como fallido sin reintentar más
            $this->fail($e);
            return;
        }
        
        // Para errores temporales (red, timeout, etc.)
        Log::error("Error temporal enviando OTP por WhatsApp", [
            'phone' => $this->phone,
            'error' => $message,
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries,
            'will_retry' => $this->attempts() < $this->tries
        ]);
        
        // Marcar como fallido sin generar stacktrace completo
        $this->fail($e);
    }
    
    /**
     * Validación básica del formato del número
     */
    protected function isPhoneFormatValid(string $phone): bool
    {
        // Remover espacios y caracteres especiales
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Verificar longitud mínima y máxima razonable
        $length = strlen($cleanPhone);
        
        // Un número de teléfono válido generalmente tiene entre 7 y 15 dígitos
        if ($length < 7 || $length > 15) {
            return false;
        }
        
        // Verificar que no sea un número obviamente falso
        if (preg_match('/^(\d)\1+$/', $cleanPhone)) { // Todo el mismo dígito (111111, 222222, etc.)
            return false;
        }
        
        if (preg_match('/^1234567/', $cleanPhone)) { // Secuencia obvia
            return false;
        }
        
        return true;
    }
    
    /**
     * Determinar si el error actual es permanente
     */
    protected function isPermanentError(): bool
    {
        // Esta es una heurística simple
        // Si ya hemos intentado 2 veces y sigue fallando,
        // probablemente es un número inválido
        if ($this->attempts() >= 2) {
            return true;
        }
        
        // También podríamos verificar otros indicadores
        // como el tipo de error en los logs
        return false;
    }

    /**
     * Handle a job failure.
     * Este método se llama cuando el job falla definitivamente
     * (después de todos los reintentos o cuando llamamos ->fail())
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de OTP WhatsApp falló definitivamente", [
            'phone' => $this->phone,
            'error' => $exception->getMessage(),
            'total_attempts' => $this->attempts(),
        ]);
        
        // Aquí podrías enviar una notificación al administrador
        // o registrar en una tabla de números problemáticos
        // para revisión manual
    }
    
    /**
     * Calcular el tiempo de espera antes del siguiente reintento
     * Implementa backoff exponencial
     */
    public function backoff(): array
    {
        // Backoff exponencial: 10s, 30s, 60s
        return [10, 30, 60];
    }
}