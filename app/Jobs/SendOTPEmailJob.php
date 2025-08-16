<?php

namespace App\Jobs;

use App\Mail\OTPCodeMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOTPEmailJob implements ShouldQueue
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
        public string $email,
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
            // Crear y enviar el email
            $mail = new OTPCodeMail($this->codigo, $this->userName, $this->expirationMinutes);
            Mail::to($this->email)->send($mail);
            
            Log::info("OTP enviado exitosamente a {$this->email} mediante job en cola");
        } catch (\Symfony\Component\Mailer\Exception\UnexpectedResponseException $e) {
            // Manejar específicamente el error 450 de rate limiting
            if ($this->isRateLimitError($e)) {
                Log::info("Rate limit alcanzado enviando OTP a {$this->email}. El email será reintentado automáticamente.", [
                    'email' => $this->email,
                    'response_code' => $this->extractResponseCode($e->getMessage()),
                    'attempt' => $this->attempts(),
                    'max_attempts' => $this->tries
                ]);
                
                // Re-lanzar para reintento automático con backoff
                throw $e;
            } else {
                // Para otros errores de respuesta SMTP, log como error
                Log::error("Error SMTP enviando OTP a {$this->email} mediante job: " . $e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error("Error enviando OTP a {$this->email} mediante job: " . $e->getMessage());
            
            // Re-lanzar la excepción para que el job se reintente
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Manejar fallo final según el tipo de error
        if ($exception instanceof \Symfony\Component\Mailer\Exception\UnexpectedResponseException 
            && $this->isRateLimitError($exception)) {
            Log::warning("Job de envío de OTP falló definitivamente por rate limiting para {$this->email}. " .
                        "Considera revisar la configuración de rate limits del proveedor de email.", [
                'email' => $this->email,
                'total_attempts' => $this->tries,
                'error_type' => 'rate_limit'
            ]);
        } else {
            Log::error("Job de envío de OTP falló definitivamente para {$this->email}: " . $exception->getMessage());
        }
    }

    /**
     * Verificar si el error es debido a rate limiting (código 450)
     */
    private function isRateLimitError(\Symfony\Component\Mailer\Exception\UnexpectedResponseException $exception): bool
    {
        $message = $exception->getMessage();
        
        // Buscar código 450 en el mensaje
        if (preg_match('/code\s*"?450"?/i', $message)) {
            return true;
        }
        
        // Buscar patrones típicos de rate limiting
        $rateLimitPatterns = [
            '/too many requests/i',
            '/rate limit/i',
            '/requests per second/i',
            '/throttle/i'
        ];
        
        foreach ($rateLimitPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extraer código de respuesta del mensaje de error
     */
    private function extractResponseCode(string $message): ?string
    {
        if (preg_match('/code\s*"?(\d+)"?/i', $message, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
