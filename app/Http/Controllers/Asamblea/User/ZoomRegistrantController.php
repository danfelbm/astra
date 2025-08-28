<?php

namespace App\Http\Controllers\Asamblea\User;

use App\Http\Controllers\Core\UserController;

use App\Jobs\Asamblea\ProcessZoomRegistrationJob;
use App\Models\Asamblea\Asamblea;
use App\Models\Asamblea\ZoomRegistrant;
use App\Services\Asamblea\ZoomApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para gestión de registros de participantes en Zoom API
 */
class ZoomRegistrantController extends UserController
{
    private ZoomApiService $zoomApiService;

    public function __construct(ZoomApiService $zoomApiService)
    {
        $this->zoomApiService = $zoomApiService;
    }

    /**
     * Registrar al usuario actual en una reunión de Zoom (ASÍNCRONO)
     * 
     * POST /api/zoom/registrants/register
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'asamblea_id' => 'required|integer|exists:asambleas,id'
            ]);

            $asambleaId = $request->input('asamblea_id');
            $user = auth()->user();

            // Obtener la asamblea
            $asamblea = Asamblea::findOrFail($asambleaId);

            // Verificar que la asamblea use API
            if ($asamblea->zoom_integration_type !== 'api') {
                return response()->json([
                    'success' => false,
                    'error' => 'Esta asamblea no usa la API de Zoom'
                ], 400);
            }

            // Verificar que la asamblea tenga Zoom habilitado
            if (!$asamblea->zoom_enabled || !$asamblea->zoom_meeting_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'La videoconferencia no está habilitada para esta asamblea'
                ], 400);
            }

            // Verificar si el usuario ya tiene un registro
            $existingRegistration = ZoomRegistrant::forAsamblea($asamblea->id)
                ->forUser($user->id)
                ->first();

            if ($existingRegistration) {
                // Si ya está completado, retornar información existente
                if ($existingRegistration->isCompleted()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Ya estás registrado en esta reunión',
                        'registration' => [
                            'id' => $existingRegistration->id,
                            'status' => $existingRegistration->status,
                            'status_message' => $existingRegistration->getRegistrationStatusMessage(),
                            'zoom_join_url' => $existingRegistration->zoom_join_url,
                            'zoom_registrant_id' => $existingRegistration->zoom_registrant_id,
                            'registered_at' => $existingRegistration->registered_at,
                        ]
                    ]);
                }

                // Si está pending o processing, retornar estado actual
                if ($existingRegistration->isPending() || $existingRegistration->isProcessing()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Tu registro está siendo procesado',
                        'registration' => [
                            'id' => $existingRegistration->id,
                            'status' => $existingRegistration->status,
                            'status_message' => $existingRegistration->getRegistrationStatusMessage(),
                            'registered_at' => $existingRegistration->registered_at,
                        ]
                    ]);
                }

                // Si falló, permitir reintento eliminando el registro anterior
                if ($existingRegistration->isFailed()) {
                    Log::info('Eliminando registro fallido para permitir reintento', [
                        'user_id' => $user->id,
                        'asamblea_id' => $asamblea->id,
                        'old_registration_id' => $existingRegistration->id,
                        'error_message' => $existingRegistration->error_message
                    ]);
                    
                    // Verificar si es un error que permite reintento
                    $errorMessage = $existingRegistration->error_message ?? '';
                    $permanentErrors = [
                        'no existe',
                        'fue eliminada',
                        'capacidad máxima',
                        'no tiene permisos'
                    ];
                    
                    $isPermanentError = false;
                    foreach ($permanentErrors as $error) {
                        if (stripos($errorMessage, $error) !== false) {
                            $isPermanentError = true;
                            break;
                        }
                    }
                    
                    if ($isPermanentError) {
                        // Error permanente, no permitir reintento
                        return response()->json([
                            'success' => false,
                            'error' => 'No se puede reintentar: ' . $errorMessage,
                            'permanent_error' => true
                        ], 400);
                    }
                    
                    // Error temporal, eliminar y permitir reintento
                    $existingRegistration->delete();
                }
            }

            // Verificar horarios específicos para API
            if (!$asamblea->zoomDisponibleParaUnirse()) {
                if ($asamblea->zoom_integration_type === 'api' && $asamblea->zoom_registration_open_date) {
                    $now = now();
                    if ($now < $asamblea->zoom_registration_open_date) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Las inscripciones aún no están abiertas'
                        ], 400);
                    } else {
                        return response()->json([
                            'success' => false,
                            'error' => 'Las inscripciones ya han cerrado'
                        ], 400);
                    }
                }
                
                return response()->json([
                    'success' => false,
                    'error' => 'La reunión no está disponible en este momento'
                ], 400);
            }

            // Encolar job para procesamiento completo (SIN crear registro aún)
            ProcessZoomRegistrationJob::dispatch($user->id, $asamblea->id);

            Log::info('Registro Zoom encolado para procesamiento asíncrono', [
                'user_id' => $user->id,
                'asamblea_id' => $asamblea->id
            ]);

            // Respuesta inmediata: procesando
            return response()->json([
                'success' => true,
                'processing' => true,
                'message' => 'Procesando registro en Zoom...',
                'user_id' => $user->id,
                'asamblea_id' => $asamblea->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error en registro de participante Zoom', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'asamblea_id' => $request->input('asamblea_id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener el estado del registro para una asamblea
     * 
     * GET /api/zoom/registrants/{asamblea}/status
     */
    public function status(Asamblea $asamblea): JsonResponse
    {
        try {
            $user = auth()->user();

            // Verificar que la asamblea use API
            if ($asamblea->zoom_integration_type !== 'api') {
                return response()->json([
                    'success' => false,
                    'error' => 'Esta asamblea no usa la API de Zoom'
                ], 400);
            }

            // Verificar si el usuario puede registrarse
            $canRegister = $this->zoomApiService->canUserRegister($asamblea, $user);
            
            // Obtener registro existente si existe
            $existingRegistration = $this->zoomApiService->getUserRegistration($asamblea, $user);

            // Si no hay registro, podría estar procesándose
            if (!$existingRegistration) {
                // Verificar si hay jobs pendientes para este usuario/asamblea
                // Obtenemos todos los jobs de la cola y verificamos el contenido deserializado
                $pendingJobs = 0;
                $jobs = \DB::table('jobs')
                    ->where('queue', config('queue.zoom_registration_queue', 'zoom-registrations'))
                    ->get();
                
                foreach ($jobs as $job) {
                    $payload = json_decode($job->payload, true);
                    if (isset($payload['data']['command'])) {
                        // Deserializar el comando PHP
                        $command = @unserialize($payload['data']['command']);
                        if ($command && 
                            isset($command->userId) && $command->userId == $user->id &&
                            isset($command->asambleaId) && $command->asambleaId == $asamblea->id) {
                            $pendingJobs++;
                        }
                    }
                }

                if ($pendingJobs > 0) {
                    return response()->json([
                        'success' => true,
                        'processing' => true,
                        'message' => 'Procesando registro en Zoom...',
                        'asamblea' => [
                            'id' => $asamblea->id,
                            'nombre' => $asamblea->nombre,
                            'zoom_api_message_enabled' => $asamblea->zoom_api_message_enabled,
                            'zoom_api_message' => $asamblea->zoom_api_message,
                        ]
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'can_register' => $canRegister['can_register'],
                'reason' => $canRegister['reason'],
                'asamblea' => [
                    'id' => $asamblea->id,
                    'nombre' => $asamblea->nombre,
                    'zoom_enabled' => $asamblea->zoom_enabled,
                    'zoom_integration_type' => $asamblea->zoom_integration_type,
                    'zoom_meeting_id' => $asamblea->zoom_meeting_id,
                    'zoom_api_message_enabled' => $asamblea->zoom_api_message_enabled,
                    'zoom_api_message' => $asamblea->zoom_api_message,
                    'estado' => $asamblea->estado,
                    'estado_label' => $asamblea->getEstadoLabel(),
                    'estado_zoom' => $asamblea->getZoomEstado(),
                    'estado_zoom_mensaje' => $asamblea->getZoomEstadoMensaje(),
                ],
                'existing_registration' => $existingRegistration ? [
                    'id' => $existingRegistration->id,
                    'zoom_join_url' => $existingRegistration->zoom_join_url,
                    'zoom_registrant_id' => $existingRegistration->zoom_registrant_id,
                    'zoom_topic' => $existingRegistration->zoom_topic,
                    'zoom_start_time' => $existingRegistration->zoom_start_time,
                    'status' => $existingRegistration->status,
                    'status_message' => $existingRegistration->getRegistrationStatusMessage(),
                    'registered_at' => $existingRegistration->registered_at,
                ] : null
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estado de registro Zoom', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'asamblea_id' => $asamblea->id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Eliminar el registro de un usuario (cancelar registro)
     * 
     * DELETE /api/zoom/registrants/{asamblea}
     */
    public function destroy(Asamblea $asamblea): JsonResponse
    {
        try {
            $user = auth()->user();

            // Buscar el registro existente
            $registration = $this->zoomApiService->getUserRegistration($asamblea, $user);

            if (!$registration) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes un registro activo para esta reunión'
                ], 404);
            }

            // Eliminar el registro local (no eliminamos en Zoom para mantener registro)
            $registration->delete();

            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error eliminando registro Zoom', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'asamblea_id' => $asamblea->id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Verificar el estado de un registro específico
     * 
     * GET /api/zoom/registrants/{registrant}/check-status
     */
    public function checkStatus(int $registrantId): JsonResponse
    {
        try {
            $user = auth()->user();

            // Buscar el registro y verificar que pertenezca al usuario autenticado
            $registration = ZoomRegistrant::with(['asamblea', 'user'])
                ->where('id', $registrantId)
                ->where('user_id', $user->id)
                ->first();

            if (!$registration) {
                return response()->json([
                    'success' => false,
                    'error' => 'Registro no encontrado o no tienes permisos para acceder a él'
                ], 404);
            }

            // Construir respuesta completa con el estado actual
            $response = [
                'success' => true,
                'registration' => [
                    'id' => $registration->id,
                    'status' => $registration->status,
                    'status_message' => $registration->getRegistrationStatusMessage(),
                    'registered_at' => $registration->registered_at,
                    'processing_started_at' => $registration->processing_started_at,
                    'error_message' => $registration->error_message,
                ],
                'asamblea' => [
                    'id' => $registration->asamblea->id,
                    'nombre' => $registration->asamblea->nombre,
                ]
            ];

            // Incluir información de Zoom solo si el registro está completado
            if ($registration->isCompleted()) {
                $response['registration']['zoom_join_url'] = $registration->zoom_join_url;
                $response['registration']['zoom_registrant_id'] = $registration->zoom_registrant_id;
                $response['registration']['zoom_topic'] = $registration->zoom_topic;
                $response['registration']['zoom_start_time'] = $registration->zoom_start_time;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error verificando estado de registro Zoom', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'registrant_id' => $registrantId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }
}
