<?php

namespace App\Services;

use App\Models\Asamblea;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    private string $apiKey;
    private string $apiSecret;
    private string $sdkKey;
    private string $sdkSecret;
    private string $baseUrl;
    
    public function __construct()
    {
        $this->apiKey = config('services.zoom.api_key');
        $this->apiSecret = config('services.zoom.api_secret');
        $this->sdkKey = config('services.zoom.sdk_key');
        $this->sdkSecret = config('services.zoom.sdk_secret');
        $this->baseUrl = 'https://api.zoom.us/v2';
    }
    
    /**
     * Generar signature JWT para SDK de Zoom Meeting
     */
    public function generateSignature(string $meetingNumber, int $role, int $videoWebRtcMode = 1): string
    {
        $payload = [
            'iss' => $this->sdkKey,
            'exp' => time() + 3600, // Expira en 1 hora
            'alg' => 'HS256',
            'aud' => 'zoom',
            'appKey' => $this->sdkKey,
            'tokenExp' => time() + 3600,
            'meetingNumber' => $meetingNumber,
            'role' => $role
        ];
        
        return $this->createJWT($payload, $this->sdkSecret);
    }
    
    /**
     * Crear una reunión programada en Zoom
     */
    public function createMeeting(Asamblea $asamblea): array
    {
        try {
            // Generar JWT para API de Zoom
            $apiToken = $this->generateApiToken();
            
            $meetingData = [
                'topic' => $asamblea->nombre,
                'type' => 2, // Reunión programada
                'start_time' => $asamblea->fecha_inicio->toISOString(),
                'duration' => $asamblea->fecha_inicio->diffInMinutes($asamblea->fecha_fin),
                'timezone' => 'America/Bogota',
                'password' => $this->generateMeetingPassword(),
                'agenda' => $asamblea->descripcion ?? 'Asamblea: ' . $asamblea->nombre,
                'settings' => [
                    'host_video' => true,
                    'participant_video' => false,
                    'cn_meeting' => false,
                    'in_meeting' => false,
                    'join_before_host' => false,
                    'mute_upon_entry' => true,
                    'watermark' => false,
                    'use_pmi' => false,
                    'approval_type' => 0, // Aprobación manual
                    'audio' => 'both',
                    'auto_recording' => 'none',
                    'waiting_room' => true,
                    'allow_multiple_devices' => false
                ]
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/users/me/meetings', $meetingData);
            
            if ($response->successful()) {
                $meeting = $response->json();
                
                // Actualizar la asamblea con datos de Zoom
                $asamblea->update([
                    'zoom_meeting_id' => $meeting['id'],
                    'zoom_meeting_password' => $meeting['password'],
                    'zoom_join_url' => $meeting['join_url'],
                    'zoom_start_url' => $meeting['start_url'],
                    'zoom_created_at' => now(),
                    'zoom_settings' => [
                        'host_video' => $meetingData['settings']['host_video'],
                        'participant_video' => $meetingData['settings']['participant_video'],
                        'waiting_room' => $meetingData['settings']['waiting_room'],
                        'mute_upon_entry' => $meetingData['settings']['mute_upon_entry'],
                        'auto_recording' => $meetingData['settings']['auto_recording']
                    ]
                ]);
                
                Log::info('Reunión de Zoom creada exitosamente', [
                    'asamblea_id' => $asamblea->id,
                    'zoom_meeting_id' => $meeting['id']
                ]);
                
                return [
                    'success' => true,
                    'meeting' => $meeting,
                    'message' => 'Reunión creada exitosamente'
                ];
            }
            
            Log::error('Error creando reunión de Zoom', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            
            return [
                'success' => false,
                'error' => 'Error al crear la reunión: ' . $response->body(),
                'message' => 'No se pudo crear la reunión de Zoom'
            ];
            
        } catch (Exception $e) {
            Log::error('Excepción creando reunión de Zoom', [
                'exception' => $e->getMessage(),
                'asamblea_id' => $asamblea->id
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error interno al crear la reunión'
            ];
        }
    }
    
    /**
     * Actualizar una reunión existente en Zoom
     */
    public function updateMeeting(Asamblea $asamblea): array
    {
        if (!$asamblea->zoom_meeting_id) {
            return $this->createMeeting($asamblea);
        }
        
        try {
            $apiToken = $this->generateApiToken();
            
            $meetingData = [
                'topic' => $asamblea->nombre,
                'start_time' => $asamblea->fecha_inicio->toISOString(),
                'duration' => $asamblea->fecha_inicio->diffInMinutes($asamblea->fecha_fin),
                'timezone' => 'America/Bogota',
                'agenda' => $asamblea->descripcion ?? 'Asamblea: ' . $asamblea->nombre,
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
                'Content-Type' => 'application/json',
            ])->patch($this->baseUrl . '/meetings/' . $asamblea->zoom_meeting_id, $meetingData);
            
            if ($response->successful()) {
                Log::info('Reunión de Zoom actualizada exitosamente', [
                    'asamblea_id' => $asamblea->id,
                    'zoom_meeting_id' => $asamblea->zoom_meeting_id
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Reunión actualizada exitosamente'
                ];
            }
            
            return [
                'success' => false,
                'error' => $response->body(),
                'message' => 'No se pudo actualizar la reunión'
            ];
            
        } catch (Exception $e) {
            Log::error('Error actualizando reunión de Zoom', [
                'exception' => $e->getMessage(),
                'asamblea_id' => $asamblea->id
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error interno al actualizar la reunión'
            ];
        }
    }
    
    /**
     * Eliminar una reunión de Zoom
     */
    public function deleteMeeting(Asamblea $asamblea): array
    {
        if (!$asamblea->zoom_meeting_id) {
            return ['success' => true, 'message' => 'No hay reunión para eliminar'];
        }
        
        try {
            $apiToken = $this->generateApiToken();
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
            ])->delete($this->baseUrl . '/meetings/' . $asamblea->zoom_meeting_id);
            
            if ($response->successful()) {
                // Limpiar datos de Zoom de la asamblea
                $asamblea->update([
                    'zoom_meeting_id' => null,
                    'zoom_meeting_password' => null,
                    'zoom_join_url' => null,
                    'zoom_start_url' => null,
                    'zoom_created_at' => null,
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Reunión eliminada exitosamente'
                ];
            }
            
            return [
                'success' => false,
                'error' => $response->body(),
                'message' => 'No se pudo eliminar la reunión'
            ];
            
        } catch (Exception $e) {
            Log::error('Error eliminando reunión de Zoom', [
                'exception' => $e->getMessage(),
                'asamblea_id' => $asamblea->id
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error interno al eliminar la reunión'
            ];
        }
    }
    
    /**
     * Verificar si un usuario puede unirse a una asamblea
     */
    public function canUserJoinMeeting(Asamblea $asamblea, User $user): array
    {
        // Verificar si la asamblea tiene Zoom habilitado
        if (!$asamblea->zoom_enabled || !$asamblea->zoom_meeting_id) {
            return [
                'can_join' => false,
                'reason' => 'La videoconferencia no está habilitada para esta asamblea'
            ];
        }
        
        // Verificar si el usuario es participante
        $esParticipante = $asamblea->participantes()->where('usuario_id', $user->id)->exists();
        if (!$esParticipante) {
            return [
                'can_join' => false,
                'reason' => 'No eres participante de esta asamblea'
            ];
        }
        
        // Verificar horarios
        $now = Carbon::now();
        $inicioPermitido = $asamblea->fecha_inicio->subMinutes(15); // Permitir entrar 15 min antes
        $finPermitido = $asamblea->fecha_fin->addMinutes(30); // Permitir hasta 30 min después
        
        if ($now < $inicioPermitido) {
            return [
                'can_join' => false,
                'reason' => 'La videoconferencia aún no está disponible. Podrás unirte 15 minutos antes del inicio.',
                'available_at' => $inicioPermitido->toISOString()
            ];
        }
        
        if ($now > $finPermitido) {
            return [
                'can_join' => false,
                'reason' => 'La videoconferencia ya finalizó'
            ];
        }
        
        return [
            'can_join' => true,
            'meeting_id' => $asamblea->zoom_meeting_id,
            'password' => $asamblea->zoom_meeting_password
        ];
    }
    
    /**
     * Obtener el rol de Zoom para un usuario en una asamblea
     */
    public function getUserRole(Asamblea $asamblea, User $user): int
    {
        $participacion = $asamblea->participantes()
            ->where('usuario_id', $user->id)
            ->first();
            
        if (!$participacion) {
            return 0; // Participante por defecto
        }
        
        // 1 = Host, 0 = Participante
        return in_array($participacion->pivot->tipo_participacion, ['moderador', 'secretario']) ? 1 : 0;
    }
    
    /**
     * Generar contraseña para reunión
     */
    private function generateMeetingPassword(): string
    {
        return substr(str_shuffle('0123456789'), 0, 6);
    }
    
    /**
     * Generar JWT para API de Zoom
     */
    private function generateApiToken(): string
    {
        $payload = [
            'iss' => $this->apiKey,
            'exp' => time() + 3600, // Expira en 1 hora
            'alg' => 'HS256',
            'aud' => 'zoom'
        ];
        
        return $this->createJWT($payload, $this->apiSecret);
    }
    
    /**
     * Crear JWT token
     */
    private function createJWT(array $payload, string $secret): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
}