<?php

namespace App\Jobs\Core;

use App\Mail\Core\UpdateRejectedMail;
use App\Models\Core\UserUpdateRequest;
use App\Services\Core\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyUpdateRejectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected UserUpdateRequest $updateRequest;

    /**
     * Create a new job instance.
     */
    public function __construct(UserUpdateRequest $updateRequest)
    {
        $this->updateRequest = $updateRequest;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsappService): void
    {
        try {
            $user = $this->updateRequest->user;
            
            // IMPORTANTE: Enviar notificaciones a los NUEVOS datos proporcionados por el usuario
            // Si el usuario intentó actualizar sus datos, necesita recibir la respuesta en esos nuevos datos
            
            // Enviar notificación por email al NUEVO email (si fue proporcionado)
            $emailToNotify = $this->updateRequest->new_email ?? $user->email;
            
            if ($emailToNotify) {
                Mail::to($emailToNotify)->send(
                    new UpdateRejectedMail($this->updateRequest)
                );
                
                Log::info('Email de rechazo enviado', [
                    'user_id' => $user->id,
                    'email_enviado_a' => $emailToNotify,
                    'es_email_nuevo' => $emailToNotify === $this->updateRequest->new_email,
                    'request_id' => $this->updateRequest->id
                ]);
            }
            
            // Enviar notificación por WhatsApp al NUEVO teléfono (si fue proporcionado)
            $phoneToNotify = $this->updateRequest->new_telefono ?? $user->telefono;
            
            if ($phoneToNotify) {
                $message = "❌ *Solicitud de Actualización Rechazada*\n\n";
                $message .= "Lamentamos informarte que tu solicitud de actualización de datos ha sido rechazada.\n\n";
                
                if ($this->updateRequest->admin_notes) {
                    $message .= "📌 *Motivo del rechazo:*\n";
                    $message .= "{$this->updateRequest->admin_notes}\n\n";
                }
                
                $message .= "Por favor, verifica la información enviada y vuelve a intentar.\n\n";
                $message .= "Si tienes dudas, contacta con soporte.";
                
                $whatsappService->sendMessage($phoneToNotify, $message);
                
                Log::info('WhatsApp de rechazo enviado', [
                    'user_id' => $user->id,
                    'telefono_enviado_a' => $phoneToNotify,
                    'es_telefono_nuevo' => $phoneToNotify === $this->updateRequest->new_telefono,
                    'request_id' => $this->updateRequest->id
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error enviando notificación de rechazo', [
                'request_id' => $this->updateRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Determina el tiempo de espera antes de reintentar el job.
     */
    public function backoff(): array
    {
        return [60, 120, 300]; // Reintentar después de 1m, 2m, 5m
    }
    
    /**
     * Determina el número máximo de intentos.
     */
    public function tries(): int
    {
        return 3;
    }
}
