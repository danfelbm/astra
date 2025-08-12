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
        Log::error("Job de envío de OTP falló definitivamente para {$this->email}: " . $exception->getMessage());
    }
}
