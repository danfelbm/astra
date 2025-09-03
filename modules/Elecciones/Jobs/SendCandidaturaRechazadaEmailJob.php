<?php

namespace Modules\Elecciones\Jobs;

use Modules\Core\Jobs\Middleware\WithRateLimiting;
use Modules\Elecciones\Mail\CandidaturaRechazadaMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCandidaturaRechazadaEmailJob implements ShouldQueue
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
     * Create a new job instance.
     */
    public function __construct(
        public string $email,
        public string $userName,
        public int $candidaturaId,
        public string $comentarios
    ) {
        // Inicializar la cola dedicada para emails
        $this->initializeRateLimitedQueue();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Crear y enviar el email
            $mail = new CandidaturaRechazadaMail($this->userName, $this->candidaturaId, $this->comentarios);
            Mail::to($this->email)->send($mail);
            
            Log::info("Notificación de candidatura rechazada enviada exitosamente a {$this->email}", [
                'email' => $this->email,
                'candidatura_id' => $this->candidaturaId
            ]);
        } catch (\Symfony\Component\Mailer\Exception\UnexpectedResponseException $e) {
            // Manejar específicamente el error 450 de rate limiting
            if ($this->isRateLimitError($e)) {
                Log::info("Rate limit alcanzado enviando notificación de rechazo a {$this->email}. El email será reintentado automáticamente.", [
                    'email' => $this->email,
                    'candidatura_id' => $this->candidaturaId,
                    'response_code' => $this->extractResponseCode($e->getMessage()),
                    'attempt' => $this->attempts(),
                    'max_attempts' => $this->tries
                ]);
                
                // Re-lanzar para reintento automático con backoff
                throw $e;
            } else {
                // Para otros errores de respuesta SMTP, log como error
                Log::error("Error SMTP enviando notificación de rechazo a {$this->email}: " . $e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error("Error enviando notificación de rechazo a {$this->email}: " . $e->getMessage());
            
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
            Log::warning("Job de notificación de rechazo falló definitivamente por rate limiting para {$this->email}. " .
                        "Considera revisar la configuración de rate limits del proveedor de email.", [
                'email' => $this->email,
                'candidatura_id' => $this->candidaturaId,
                'total_attempts' => $this->tries,
                'error_type' => 'rate_limit'
            ]);
        } else {
            Log::error("Job de notificación de rechazo falló definitivamente para {$this->email}", [
                'email' => $this->email,
                'candidatura_id' => $this->candidaturaId,
                'error' => $exception->getMessage(),
                'total_attempts' => $this->tries,
                'error_type' => 'general'
            ]);
        }
    }

    /**
     * Verificar si es un error de rate limiting
     */
    private function isRateLimitError(\Symfony\Component\Mailer\Exception\UnexpectedResponseException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, '450') || 
               str_contains($message, 'rate') || 
               str_contains($message, 'too many') ||
               str_contains($message, 'limit');
    }

    /**
     * Extraer código de respuesta del mensaje de error
     */
    private function extractResponseCode(string $message): ?string
    {
        if (preg_match('/\b(4\d{2}|5\d{2})\b/', $message, $matches)) {
            return $matches[1];
        }
        return null;
    }
}