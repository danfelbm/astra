<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asamblea;
use App\Services\ZoomApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para gestión de registros de participantes en Zoom API
 */
class ZoomRegistrantController extends Controller
{
    private ZoomApiService $zoomApiService;

    public function __construct(ZoomApiService $zoomApiService)
    {
        $this->zoomApiService = $zoomApiService;
    }

    /**
     * Registrar al usuario actual en una reunión de Zoom
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

            // Verificar permisos básicos (podríamos añadir más verificaciones)
            // Por ejemplo, verificar si el usuario es participante de la asamblea
            // Esto depende de la lógica de negocio específica

            // Verificar si el usuario puede registrarse
            $canRegister = $this->zoomApiService->canUserRegister($asamblea, $user);
            
            if (!$canRegister['can_register']) {
                return response()->json([
                    'success' => false,
                    'error' => $canRegister['reason'],
                    'existing_registration' => $canRegister['existing_registration'] ?? null
                ], 400);
            }

            // Registrar al participante
            $result = $this->zoomApiService->registerParticipant($asamblea, $user);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'registration' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['message']
                ], 500);
            }

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
                    'status' => $existingRegistration->getStatus(),
                    'status_message' => $existingRegistration->getStatusMessage(),
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
}
