<?php

namespace App\Jobs\Core;

use App\Mail\Core\UpdateApprovedMail;
use App\Models\Core\UserUpdateRequest;
use App\Services\Core\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyUpdateApprovalJob implements ShouldQueue
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
            
            // IMPORTANTE: Enviar notificaciones a los NUEVOS datos, no a los antiguos
            // El usuario actualizó sus datos precisamente porque los anteriores podrían no funcionar
            
            // Enviar notificación por email al NUEVO email (si fue actualizado)
            $emailToNotify = $this->updateRequest->new_email ?? $user->email;
            
            if ($emailToNotify) {
                Mail::to($emailToNotify)->send(
                    new UpdateApprovedMail($this->updateRequest)
                );
                
                Log::info('Email de aprobación enviado', [
                    'user_id' => $user->id,
                    'email_enviado_a' => $emailToNotify,
                    'es_email_nuevo' => $emailToNotify === $this->updateRequest->new_email,
                    'request_id' => $this->updateRequest->id
                ]);
            }
            
            // Enviar notificación por WhatsApp al NUEVO teléfono (si fue actualizado)
            $phoneToNotify = $this->updateRequest->new_telefono ?? $user->telefono;
            
            if ($phoneToNotify) {
                $message = "✅ *Actualización Aprobada*\n\n";
                $message .= "Tu solicitud de actualización de datos ha sido aprobada.\n";
                
                if ($this->updateRequest->new_email) {
                    $message .= "📧 Nuevo email: {$this->updateRequest->new_email}\n";
                }
                
                if ($this->updateRequest->new_telefono) {
                    $message .= "📱 Nuevo teléfono: {$this->updateRequest->new_telefono}\n";
                }
                
                $message .= "\nTus datos han sido actualizados exitosamente.";
                
                if ($this->updateRequest->admin_notes) {
                    $message .= "\n\n💬 Nota del administrador: {$this->updateRequest->admin_notes}";
                }
                
                $whatsappService->sendMessage($phoneToNotify, $message);
                
                Log::info('WhatsApp de aprobación enviado', [
                    'user_id' => $user->id,
                    'telefono_enviado_a' => $phoneToNotify,
                    'es_telefono_nuevo' => $phoneToNotify === $this->updateRequest->new_telefono,
                    'request_id' => $this->updateRequest->id
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error enviando notificación de aprobación', [
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
