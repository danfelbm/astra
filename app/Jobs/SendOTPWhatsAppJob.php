<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOTPWhatsAppJob implements ShouldQueue
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
     * Create a new job instance.
     */
    public function __construct(
        public string $phone,
        public string $codigo,
        public string $userName,
        public int $expirationMinutes = 10
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
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
                Log::info("OTP enviado exitosamente por WhatsApp mediante job en cola", [
                    'phone' => $this->phone,
                    'code' => substr($this->codigo, 0, 2) . '****' // Log parcial por seguridad
                ]);
            } else {
                // Si falla, lanzar excepción para reintentar
                throw new \Exception("No se pudo enviar el mensaje de WhatsApp");
            }
            
        } catch (\Exception $e) {
            Log::error("Error enviando OTP por WhatsApp mediante job: " . $e->getMessage(), [
                'phone' => $this->phone,
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
        Log::error("Job de envío de OTP por WhatsApp falló definitivamente", [
            'phone' => $this->phone,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Aquí podrías implementar un fallback a email si es necesario
        // O notificar al administrador del fallo
    }

    /**
     * Determinar el timeout del job.
     */
    public function retryUntil(): \DateTime
    {
        // El job puede reintentar hasta 5 minutos después de creado
        return now()->addMinutes(5);
    }
}