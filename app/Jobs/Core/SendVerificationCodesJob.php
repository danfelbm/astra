<?php

namespace App\Jobs\Core;

use App\Mail\Core\UserVerificationMail;
use App\Models\Core\UserVerificationRequest;
use App\Services\Core\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendVerificationCodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $verificationRequestId;
    protected ?string $emailCode;
    protected ?string $whatsappCode;
    protected ?string $userEmail;
    protected ?string $userPhone;
    protected string $userName;

    /**
     * Create a new job instance.
     */
    public function __construct(UserVerificationRequest $verificationRequest)
    {
        // Guardar solo los datos primitivos necesarios, no el modelo completo
        $this->verificationRequestId = $verificationRequest->id;
        $this->emailCode = $verificationRequest->verification_code_email;
        $this->whatsappCode = $verificationRequest->verification_code_whatsapp;
        $this->userEmail = $verificationRequest->user->email ?? null;
        $this->userPhone = $verificationRequest->user->telefono ?? null;
        $this->userName = $verificationRequest->user->name ?? 'Usuario';
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsappService): void
    {
        try {
            Log::debug('Job iniciado para enviar códigos', [
                'request_id' => $this->verificationRequestId,
                'has_email_code' => !!$this->emailCode,
                'has_whatsapp_code' => !!$this->whatsappCode,
                'has_user_email' => !!$this->userEmail,
                'has_user_phone' => !!$this->userPhone
            ]);

            // Enviar código por email si está disponible
            if ($this->emailCode && $this->userEmail) {
                Log::debug('Intentando enviar email desde Job', [
                    'request_id' => $this->verificationRequestId
                ]);
                
                try {
                    // Enviar email directamente sin usar el servicio
                    $mail = new UserVerificationMail($this->userName, $this->emailCode, 'email');
                    Mail::to($this->userEmail)->send($mail);
                    
                    Log::info('Código de verificación enviado por email', [
                        'user_email' => $this->userEmail,
                        'request_id' => $this->verificationRequestId
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error enviando email desde Job', [
                        'error' => $e->getMessage(),
                        'request_id' => $this->verificationRequestId
                    ]);
                }
            }

            // Enviar código por WhatsApp si está disponible
            if ($this->whatsappCode && $this->userPhone) {
                Log::debug('Intentando enviar WhatsApp desde Job', [
                    'request_id' => $this->verificationRequestId
                ]);
                
                try {
                    $message = $this->buildWhatsappMessage($this->userName, $this->whatsappCode);
                    $sent = $whatsappService->sendMessage($this->userPhone, $message);
                    
                    if ($sent) {
                        Log::info('Código de verificación enviado por WhatsApp', [
                            'request_id' => $this->verificationRequestId
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error enviando WhatsApp desde Job', [
                        'error' => $e->getMessage(),
                        'request_id' => $this->verificationRequestId
                    ]);
                }
            }

            Log::info('Job de códigos de verificación completado', [
                'request_id' => $this->verificationRequestId
            ]);
        } catch (\Exception $e) {
            Log::error('Error en Job de códigos de verificación', [
                'request_id' => $this->verificationRequestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-lanzar la excepción para que el job pueda reintentar
            throw $e;
        }
    }

    /**
     * Construye el mensaje de WhatsApp para verificación
     */
    protected function buildWhatsappMessage(string $userName, string $code): string
    {
        return "Hola {$userName},\n\n" .
               "Tu código de verificación para confirmar tu registro es: *{$code}*\n\n" .
               "Este código es válido por 15 minutos.\n" .
               "Si no solicitaste este código, puedes ignorar este mensaje.\n\n" .
               "Sistema de Votaciones";
    }

    /**
     * Determina el tiempo de espera antes de reintentar el job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Reintentar después de 30s, 1m, 2m
    }

    /**
     * Determina el número máximo de intentos.
     */
    public function tries(): int
    {
        return 3;
    }
}
