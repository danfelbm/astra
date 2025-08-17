<?php

namespace App\Jobs;

use App\Models\Asamblea;
use App\Models\User;
use App\Models\ZoomRegistrant;
use App\Services\ZoomApiService;
use App\Services\ZoomNotificationService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessZoomRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * El número de veces que el job puede ser intentado.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * El tiempo máximo que el job puede ejecutarse antes de timeout
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * ID del usuario y asamblea para procesar
     */
    public int $userId;
    public int $asambleaId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, int $asambleaId)
    {
        $this->userId = $userId;
        $this->asambleaId = $asambleaId;
        
        // Configurar la cola específica para registros de Zoom
        $this->onQueue(config('queue.zoom_registration_queue', 'zoom-registrations'));
    }

    /**
     * Execute the job.
     */
    public function handle(ZoomApiService $zoomApiService, ZoomNotificationService $notificationService): void
    {
        try {
            // Obtener usuario y asamblea
            $user = User::find($this->userId);
            $asamblea = Asamblea::find($this->asambleaId);

            if (!$user || !$asamblea) {
                Log::warning('Usuario o asamblea no encontrados', [
                    'user_id' => $this->userId,
                    'asamblea_id' => $this->asambleaId
                ]);
                return;
            }

            // Verificar que no exista ya un registro
            $existingRegistration = ZoomRegistrant::forAsamblea($asamblea->id)
                ->forUser($user->id)
                ->first();

            if ($existingRegistration) {
                Log::info('Ya existe registro para este usuario y asamblea', [
                    'existing_registration_id' => $existingRegistration->id,
                    'user_id' => $user->id,
                    'asamblea_id' => $asamblea->id
                ]);
                return;
            }

            Log::info('Iniciando procesamiento de registro Zoom', [
                'user_id' => $user->id,
                'asamblea_id' => $asamblea->id,
                'attempt' => $this->attempts()
            ]);

            // Realizar el registro en Zoom API
            $result = $zoomApiService->callZoomRegistrationApi($asamblea, $user);

            if ($result['success']) {
                // Crear registro completo directamente
                $registration = ZoomRegistrant::create([
                    'asamblea_id' => $asamblea->id,
                    'user_id' => $user->id,
                    'zoom_registrant_id' => $result['data']['zoom_registrant_id'],
                    'zoom_join_url' => $result['data']['zoom_join_url'],
                    'zoom_participant_pin_code' => $result['data']['zoom_participant_pin_code'] ?? null,
                    'zoom_topic' => $result['data']['zoom_topic'] ?? $asamblea->nombre,
                    'zoom_start_time' => $result['data']['zoom_start_time'] ?? $asamblea->fecha_inicio,
                    'zoom_occurrences' => $result['data']['zoom_occurrences'] ?? null,
                    'status' => 'completed',
                    'registered_at' => now(),
                    'processing_started_at' => now(),
                ]);

                Log::info('Registro Zoom completado exitosamente', [
                    'zoom_registrant_id' => $registration->id,
                    'zoom_api_registrant_id' => $result['data']['zoom_registrant_id'],
                    'user_id' => $user->id,
                    'asamblea_id' => $asamblea->id,
                    'attempt' => $this->attempts()
                ]);

                // Enviar notificaciones
                try {
                    $notificationResult = $notificationService->sendNotifications($registration, $user);
                    
                    Log::info('Notificaciones Zoom enviadas', [
                        'zoom_registrant_id' => $registration->id,
                        'notification_success' => $notificationResult['success'],
                        'channels_sent' => $notificationResult['channels_sent'] ?? [],
                    ]);
                } catch (Exception $notificationError) {
                    // No propagar errores de notificación
                    Log::warning('Error enviando notificaciones Zoom (no crítico)', [
                        'zoom_registrant_id' => $registration->id,
                        'user_id' => $user->id,
                        'error' => $notificationError->getMessage()
                    ]);
                }

            } else {
                throw new Exception('Error en registro Zoom: ' . $result['message']);
            }

        } catch (Exception $e) {
            Log::error('Error procesando registro Zoom', [
                'user_id' => $this->userId,
                'asamblea_id' => $this->asambleaId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries
            ]);

            // No crear registro si falla - simplemente re-lanzar para reintentos
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de registro Zoom falló definitivamente', [
            'user_id' => $this->userId,
            'asamblea_id' => $this->asambleaId,
            'error' => $exception->getMessage(),
            'total_attempts' => $this->tries,
        ]);

        // Analizar el tipo de error para determinar si crear registro fallido
        $errorMessage = $exception->getMessage();
        $shouldCreateFailedRecord = true;
        
        // Extraer código HTTP si está presente en el mensaje
        $httpCode = null;
        if (preg_match('/\[HTTP (\d+)\]/', $errorMessage, $matches)) {
            $httpCode = (int) $matches[1];
        }
        
        // Detectar errores temporales por código HTTP o mensaje
        $temporaryErrorCodes = [429, 503, 504, 502];  // Rate limit, Service unavailable, Gateway timeout, Bad gateway
        
        if ($httpCode && in_array($httpCode, $temporaryErrorCodes)) {
            $shouldCreateFailedRecord = false;
            Log::warning('Error temporal de Zoom (HTTP ' . $httpCode . ') - NO se crea registro fallido', [
                'user_id' => $this->userId,
                'asamblea_id' => $this->asambleaId,
                'http_code' => $httpCode,
                'message' => $errorMessage,
                'info' => 'El usuario puede intentar nuevamente más tarde'
            ]);
        } else {
            // Detectar errores temporales por mensaje si no hay código HTTP
            $temporaryErrors = [
                'exceeded the daily rate limit' => true,  // Error 429
                'Service Unavailable' => true,            // Error 503
                'Gateway Timeout' => true,                // Error 504
                'Too Many Requests' => true,              // Error 429 genérico
            ];
            
            foreach ($temporaryErrors as $tempError => $value) {
                if (stripos($errorMessage, $tempError) !== false) {
                    $shouldCreateFailedRecord = false;
                    Log::warning('Error temporal de Zoom - NO se crea registro fallido', [
                        'user_id' => $this->userId,
                        'asamblea_id' => $this->asambleaId,
                        'error_type' => $tempError,
                        'message' => $errorMessage,
                        'info' => 'El usuario puede intentar nuevamente más tarde'
                    ]);
                    break;
                }
            }
        }
        
        // Solo crear registro fallido para errores definitivos
        if ($shouldCreateFailedRecord) {
            try {
                $failedRegistration = ZoomRegistrant::create([
                    'asamblea_id' => $this->asambleaId,
                    'user_id' => $this->userId,
                    'status' => 'failed',
                    'error_message' => $this->parseErrorMessage($errorMessage),
                    'registered_at' => now(),
                    'processing_started_at' => now(),
                ]);

                // Encolar notificación de fallo al usuario
                NotifyZoomRegistrationFailureJob::dispatch($failedRegistration->id);

            } catch (\Exception $e) {
                Log::error('Error creando registro de fallo', [
                    'user_id' => $this->userId,
                    'asamblea_id' => $this->asambleaId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Parsear mensaje de error para hacerlo más amigable al usuario
     */
    private function parseErrorMessage(string $errorMessage): string
    {
        // Extraer solo la parte relevante del mensaje de error
        if (stripos($errorMessage, 'Meeting does not exist') !== false) {
            return 'La reunión no existe o fue eliminada';
        }
        if (stripos($errorMessage, 'Meeting has reached maximum attendee capacity') !== false) {
            return 'La reunión ha alcanzado la capacidad máxima de asistentes';
        }
        if (stripos($errorMessage, 'Cannot access meeting info') !== false) {
            return 'No se puede acceder a la información de la reunión';
        }
        if (stripos($errorMessage, 'Meeting hosting and scheduling capabilities are not allowed') !== false) {
            return 'La cuenta de Zoom no tiene permisos para gestionar reuniones';
        }
        
        // Si no es un error conocido, limpiar el mensaje
        $cleanMessage = str_replace('Error en registro Zoom: Error registrando participante en Zoom: ', '', $errorMessage);
        return 'Error técnico: ' . $cleanMessage;
    }

    /**
     * Calcular el tiempo de espera antes del siguiente reintento
     */
    public function backoff(): array
    {
        // Backoff exponencial: 30s, 60s, 120s para dar tiempo a que se resuelvan problemas de conectividad
        return [30, 60, 120];
    }
}
