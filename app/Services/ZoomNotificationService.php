<?php

namespace App\Services;

use App\Http\Controllers\ZoomRedirectController;
use App\Jobs\SendZoomAccessEmailJob;
use App\Jobs\SendZoomAccessWhatsAppJob;
use App\Models\User;
use App\Models\ZoomRegistrant;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar notificaciones de acceso Zoom
 */
class ZoomNotificationService
{
    /**
     * Enviar notificaciones de acceso Zoom por email y WhatsApp
     */
    public function sendNotifications(ZoomRegistrant $zoomRegistrant, User $user): array
    {
        try {
            // Generar enlace enmascarado
            $maskedUrl = $this->generateMaskedUrl($zoomRegistrant->id);
            
            // Preparar datos para las notificaciones
            $notificationData = $this->prepareNotificationData($zoomRegistrant, $user, $maskedUrl);
            
            // Determinar canales de envío (similar a OTPService)
            $channels = $this->determineNotificationChannels($user);
            
            // Enviar notificaciones según los canales configurados
            $results = [];
            
            foreach ($channels as $channel) {
                switch ($channel) {
                    case 'email':
                        $results['email'] = $this->sendEmailNotification($notificationData);
                        break;
                        
                    case 'whatsapp':
                        $results['whatsapp'] = $this->sendWhatsAppNotification($notificationData);
                        break;
                }
            }
            
            // Log exitoso
            Log::info('Notificaciones Zoom enviadas exitosamente', [
                'zoom_registrant_id' => $zoomRegistrant->id,
                'user_id' => $user->id,
                'asamblea_id' => $zoomRegistrant->asamblea_id,
                'channels' => $channels,
                'masked_url' => $maskedUrl
            ]);
            
            return [
                'success' => true,
                'channels_sent' => $channels,
                'masked_url' => $maskedUrl,
                'results' => $results
            ];
            
        } catch (\Exception $e) {
            Log::error('Error enviando notificaciones Zoom', [
                'zoom_registrant_id' => $zoomRegistrant->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar enlace enmascarado para acceso Zoom
     */
    public function generateMaskedUrl(int $zoomRegistrantId): string
    {
        return ZoomRedirectController::generateMaskedUrl($zoomRegistrantId);
    }

    /**
     * Preparar datos para las notificaciones
     */
    private function prepareNotificationData(ZoomRegistrant $zoomRegistrant, User $user, string $maskedUrl): array
    {
        return [
            'user' => $user,
            'zoom_registrant' => $zoomRegistrant,
            'asamblea' => $zoomRegistrant->asamblea,
            'masked_url' => $maskedUrl,
            'zoom_registrant_id' => $zoomRegistrant->zoom_registrant_id,
            'user_name' => $user->name,
            'asamblea_nombre' => $zoomRegistrant->asamblea->nombre,
            'asamblea_fecha_inicio' => $zoomRegistrant->asamblea->fecha_inicio,
            'asamblea_fecha_fin' => $zoomRegistrant->asamblea->fecha_fin,
        ];
    }

    /**
     * Determinar canales de notificación basado en configuración y datos del usuario
     */
    private function determineNotificationChannels(User $user): array
    {
        $channels = [];
        
        // Siempre enviar email si está configurado
        if ($user->email) {
            $channels[] = 'email';
        }
        
        // WhatsApp solo si está habilitado y el usuario tiene teléfono
        $whatsappEnabled = config('services.whatsapp.enabled', false);
        if ($whatsappEnabled && $user->telefono) {
            $channels[] = 'whatsapp';
        }
        
        // Si no hay canales, al menos intentar email
        if (empty($channels) && $user->email) {
            $channels[] = 'email';
        }
        
        return $channels;
    }

    /**
     * Enviar notificación por email
     */
    private function sendEmailNotification(array $data): array
    {
        try {
            // Determinar si enviar inmediatamente o usar cola (similar a OTPService)
            $sendImmediately = config('services.otp.send_immediately', true);
            
            if ($sendImmediately) {
                // Envío inmediato (síncrono)
                $job = new SendZoomAccessEmailJob($data);
                $job->handle();
                
                Log::info("Notificación Zoom enviada inmediatamente por email", [
                    'email' => $data['user']->email,
                    'asamblea_id' => $data['asamblea']->id
                ]);
                
                return ['status' => 'sent_immediately'];
            } else {
                // Envío mediante cola (asíncrono)
                SendZoomAccessEmailJob::dispatch($data);
                
                Log::info("Notificación Zoom encolada para envío por email", [
                    'email' => $data['user']->email,
                    'asamblea_id' => $data['asamblea']->id
                ]);
                
                return ['status' => 'queued'];
            }
            
        } catch (\Exception $e) {
            Log::error("Error enviando notificación Zoom por email", [
                'email' => $data['user']->email,
                'error' => $e->getMessage()
            ]);
            
            // Intentar con cola como fallback si el envío inmediato falla
            if (config('services.otp.send_immediately', true)) {
                try {
                    Log::info("Intentando envío mediante cola como fallback");
                    SendZoomAccessEmailJob::dispatch($data);
                    return ['status' => 'queued_as_fallback'];
                } catch (\Exception $fallbackError) {
                    Log::error("Fallback también falló: " . $fallbackError->getMessage());
                    return ['status' => 'failed', 'error' => $fallbackError->getMessage()];
                }
            }
            
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar notificación por WhatsApp
     */
    private function sendWhatsAppNotification(array $data): array
    {
        try {
            if (!$data['user']->telefono) {
                Log::warning("No se puede enviar notificación Zoom por WhatsApp sin número de teléfono", [
                    'user_id' => $data['user']->id
                ]);
                return ['status' => 'skipped', 'reason' => 'no_phone'];
            }

            // Determinar si enviar inmediatamente o usar cola
            $sendImmediately = config('services.otp.send_immediately', true);
            
            if ($sendImmediately) {
                // Envío inmediato (síncrono)
                $job = new SendZoomAccessWhatsAppJob($data);
                $job->handle();
                
                Log::info("Notificación Zoom enviada inmediatamente por WhatsApp", [
                    'phone' => $data['user']->telefono,
                    'asamblea_id' => $data['asamblea']->id
                ]);
                
                return ['status' => 'sent_immediately'];
            } else {
                // Envío mediante cola (asíncrono)
                SendZoomAccessWhatsAppJob::dispatch($data);
                
                Log::info("Notificación Zoom encolada para envío por WhatsApp", [
                    'phone' => $data['user']->telefono,
                    'asamblea_id' => $data['asamblea']->id
                ]);
                
                return ['status' => 'queued'];
            }
            
        } catch (\Exception $e) {
            Log::error("Error enviando notificación Zoom por WhatsApp", [
                'phone' => $data['user']->telefono ?? 'N/A',
                'error' => $e->getMessage()
            ]);
            
            // Fallback a email si está configurado y disponible
            if ($data['user']->email) {
                try {
                    Log::info("Intentando fallback a email para notificación Zoom");
                    return $this->sendEmailNotification($data);
                } catch (\Exception $fallbackError) {
                    Log::error("Fallback de email también falló: " . $fallbackError->getMessage());
                    return ['status' => 'failed', 'error' => $fallbackError->getMessage()];
                }
            }
            
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener estadísticas de envío para una asamblea
     */
    public function getNotificationStats(int $asambleaId): array
    {
        $zoomRegistrants = ZoomRegistrant::where('asamblea_id', $asambleaId)
            ->with(['user', 'access'])
            ->get();
            
        $stats = [
            'total_registrants' => $zoomRegistrants->count(),
            'with_email' => 0,
            'with_phone' => 0,
            'total_accesses' => 0,
            'unique_accesses' => 0,
        ];
        
        foreach ($zoomRegistrants as $registrant) {
            if ($registrant->user->email) {
                $stats['with_email']++;
            }
            
            if ($registrant->user->telefono) {
                $stats['with_phone']++;
            }
            
            if ($registrant->accesses) {
                $accessCount = $registrant->accesses->count();
                $stats['total_accesses'] += $accessCount;
                if ($accessCount > 0) {
                    $stats['unique_accesses']++;
                }
            }
        }
        
        return $stats;
    }

    /**
     * Reenviar notificaciones para usuarios específicos
     */
    public function resendNotifications(array $zoomRegistrantIds): array
    {
        $results = [];
        
        foreach ($zoomRegistrantIds as $id) {
            $zoomRegistrant = ZoomRegistrant::with(['user', 'asamblea'])->find($id);
            
            if ($zoomRegistrant && $zoomRegistrant->user) {
                $result = $this->sendNotifications($zoomRegistrant, $zoomRegistrant->user);
                $results[$id] = $result;
            } else {
                $results[$id] = ['success' => false, 'error' => 'Registro no encontrado'];
            }
        }
        
        return $results;
    }
}