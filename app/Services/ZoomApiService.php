<?php

namespace App\Services;

use App\Models\Asamblea;
use App\Models\User;
use App\Models\ZoomRegistrant;
use App\Services\ZoomNotificationService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para integración con API Server-to-Server OAuth de Zoom
 */
class ZoomApiService
{
    private string $clientId;
    private string $clientSecret;
    private string $accountId;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.zoom.oauth.client_id');
        $this->clientSecret = config('services.zoom.oauth.client_secret');
        $this->accountId = config('services.zoom.oauth.account_id');
        $this->baseUrl = 'https://api.zoom.us/v2';
    }

    /**
     * Obtener token de acceso OAuth
     */
    private function getAccessToken(): string
    {
        // Intentar obtener token del cache
        $cacheKey = 'zoom_oauth_token';
        $token = Cache::get($cacheKey);

        if ($token) {
            return $token;
        }

        try {
            // Crear credenciales Base64
            $credentials = base64_encode($this->clientId . ':' . $this->clientSecret);

            // Hacer petición OAuth
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => $this->accountId,
            ]);

            if ($response->failed()) {
                throw new Exception('Error obteniendo token OAuth de Zoom: ' . $response->body());
            }

            $data = $response->json();
            $token = $data['access_token'];
            $expiresIn = $data['expires_in'] ?? 3600;

            // Cachear el token (menos 5 minutos para seguridad)
            Cache::put($cacheKey, $token, $expiresIn - 300);

            return $token;

        } catch (Exception $e) {
            Log::error('Error obteniendo token OAuth de Zoom', [
                'error' => $e->getMessage(),
                'client_id' => $this->clientId
            ]);
            throw $e;
        }
    }

    /**
     * Registrar participante en una reunión de Zoom
     */
    public function registerParticipant(Asamblea $asamblea, User $user): array
    {
        try {
            // Verificar que la asamblea usa API y tiene meetingId
            if ($asamblea->zoom_integration_type !== 'api' || !$asamblea->zoom_meeting_id) {
                throw new Exception('La asamblea no está configurada para usar la API de Zoom');
            }

            // Verificar que el usuario no esté ya registrado
            $existingRegistrant = ZoomRegistrant::forAsamblea($asamblea->id)
                ->forUser($user->id)
                ->first();

            if ($existingRegistrant) {
                return [
                    'success' => true,
                    'message' => 'Usuario ya registrado',
                    'data' => $existingRegistrant->toArray()
                ];
            }

            // Obtener token de acceso
            $token = $this->getAccessToken();

            // Procesar nombre del usuario
            $nameParts = $user->splitName();

            // Aplicar prefijo si existe
            $firstName = $nameParts['first'];
            if (!empty($asamblea->zoom_prefix)) {
                $firstName = $asamblea->zoom_prefix . ' ' . $firstName;
            }

            // Preparar datos del registrante
            $registrantData = [
                'first_name' => $firstName,
                'last_name' => $nameParts['last'],
                'email' => $user->email,
                'auto_approve' => true,
            ];

            // Añadir campos opcionales si están disponibles
            if ($user->direccion) {
                $registrantData['address'] = $user->direccion;
            }

            if ($user->municipio) {
                $registrantData['city'] = $user->municipio->name;
            }

            if ($user->departamento) {
                $registrantData['state'] = $user->departamento->name;
            }

            if ($user->territorio) {
                $registrantData['country'] = $user->territorio->name;
            }

            if ($user->telefono) {
                $registrantData['phone'] = $user->telefono;
            }

            // Construir URL de la API
            $meetingId = $asamblea->zoom_meeting_id;
            $url = "{$this->baseUrl}/meetings/{$meetingId}/registrants";

            // Añadir occurrence_ids si están disponibles
            $queryParams = [];
            if ($asamblea->zoom_occurrence_ids) {
                $queryParams['occurrence_ids'] = $asamblea->zoom_occurrence_ids;
            }

            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }

            // Hacer petición a la API de Zoom
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($url, $registrantData);

            if ($response->failed()) {
                $error = $response->json();
                throw new Exception('Error registrando participante en Zoom: ' . 
                    ($error['message'] ?? $response->body()));
            }

            $responseData = $response->json();

            // Crear registro en la base de datos
            $zoomRegistrant = ZoomRegistrant::create([
                'asamblea_id' => $asamblea->id,
                'user_id' => $user->id,
                'zoom_registrant_id' => $responseData['registrant_id'],
                'zoom_join_url' => $responseData['join_url'],
                'zoom_participant_pin_code' => $responseData['participant_pin_code'] ?? null,
                'zoom_start_time' => $asamblea->fecha_inicio, // Usar fecha de la asamblea
                'zoom_topic' => $responseData['topic'] ?? $asamblea->nombre,
                'zoom_occurrences' => $responseData['occurrences'] ?? null,
                'registered_at' => now(),
            ]);

            // Enviar notificaciones automáticamente después del registro exitoso
            try {
                $notificationService = new ZoomNotificationService();
                $notificationResult = $notificationService->sendNotifications($zoomRegistrant, $user);
                
                Log::info('Intento de notificaciones Zoom completado', [
                    'zoom_registrant_id' => $zoomRegistrant->id,
                    'user_id' => $user->id,
                    'notification_success' => $notificationResult['success'],
                    'channels_sent' => $notificationResult['channels_sent'] ?? [],
                ]);
                
            } catch (Exception $notificationError) {
                // No propagar errores de notificación para no afectar el registro exitoso
                Log::warning('Error enviando notificaciones Zoom (no crítico)', [
                    'zoom_registrant_id' => $zoomRegistrant->id,
                    'user_id' => $user->id,
                    'error' => $notificationError->getMessage()
                ]);
            }

            return [
                'success' => true,
                'message' => 'Participante registrado exitosamente',
                'data' => $zoomRegistrant->toArray()
            ];

        } catch (Exception $e) {
            Log::error('Error registrando participante en Zoom API', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'asamblea_id' => $asamblea->id,
                'meeting_id' => $asamblea->zoom_meeting_id ?? null
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener información del registro de un usuario
     */
    public function getUserRegistration(Asamblea $asamblea, User $user): ?ZoomRegistrant
    {
        return ZoomRegistrant::forAsamblea($asamblea->id)
            ->forUser($user->id)
            ->first();
    }

    /**
     * Verificar si un usuario puede registrarse en una asamblea
     */
    public function canUserRegister(Asamblea $asamblea, User $user): array
    {
        // Verificar que la asamblea use API
        if ($asamblea->zoom_integration_type !== 'api') {
            return [
                'can_register' => false,
                'reason' => 'La asamblea no usa la API de Zoom'
            ];
        }

        // Verificar que la asamblea tenga Zoom habilitado
        if (!$asamblea->zoom_enabled || !$asamblea->zoom_meeting_id) {
            return [
                'can_register' => false,
                'reason' => 'La videoconferencia no está habilitada para esta asamblea'
            ];
        }

        // Verificar que el usuario no esté ya registrado
        $existingRegistrant = $this->getUserRegistration($asamblea, $user);
        if ($existingRegistrant) {
            return [
                'can_register' => false,
                'reason' => 'Ya estás registrado en esta reunión',
                'existing_registration' => $existingRegistrant
            ];
        }

        // Verificar horarios específicos para API
        if (!$asamblea->zoomDisponibleParaUnirse()) {
            // Mensaje específico para modo API
            if ($asamblea->zoom_integration_type === 'api' && $asamblea->zoom_registration_open_date) {
                $now = now();
                if ($now < $asamblea->zoom_registration_open_date) {
                    return [
                        'can_register' => false,
                        'reason' => 'Las inscripciones aún no están abiertas'
                    ];
                } else {
                    return [
                        'can_register' => false,
                        'reason' => 'Las inscripciones ya han cerrado'
                    ];
                }
            }
            
            return [
                'can_register' => false,
                'reason' => 'La reunión no está disponible en este momento'
            ];
        }

        return [
            'can_register' => true,
            'reason' => 'Usuario puede registrarse'
        ];
    }

    /**
     * Eliminar cache del token (útil para testing)
     */
    public function clearTokenCache(): void
    {
        Cache::forget('zoom_oauth_token');
    }
}