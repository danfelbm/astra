<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asamblea;
use App\Services\ZoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZoomAuthController extends Controller
{
    private ZoomService $zoomService;
    
    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }
    
    /**
     * Generar signature JWT para unirse a una reunión de Zoom
     */
    public function generateSignature(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'meetingNumber' => 'required|string',
                'role' => 'required|integer|in:0,1',
                'videoWebRtcMode' => 'nullable|integer|in:0,1'
            ]);
            
            $meetingNumber = $request->input('meetingNumber');
            $role = $request->input('role', 0);
            $videoWebRtcMode = $request->input('videoWebRtcMode', 1);
            
            // Verificar que la reunión existe y el usuario tiene permisos
            $asamblea = Asamblea::where('zoom_meeting_id', $meetingNumber)->first();
            
            if (!$asamblea) {
                return response()->json([
                    'success' => false,
                    'error' => 'Reunión no encontrada'
                ], 404);
            }
            
            // Verificar permisos del usuario
            $canJoin = $this->zoomService->canUserJoinMeeting($asamblea, auth()->user());
            
            if (!$canJoin['can_join']) {
                return response()->json([
                    'success' => false,
                    'error' => $canJoin['reason']
                ], 403);
            }
            
            // Verificar el rol del usuario
            $userRole = $this->zoomService->getUserRole($asamblea, auth()->user());
            
            // Generar signature
            $signature = $this->zoomService->generateSignature($meetingNumber, $userRole, $videoWebRtcMode);
            
            Log::info('Signature generada para Zoom', [
                'user_id' => auth()->id(),
                'asamblea_id' => $asamblea->id,
                'meeting_number' => $meetingNumber,
                'role' => $userRole
            ]);
            
            return response()->json([
                'success' => true,
                'signature' => $signature,
                'meetingNumber' => $meetingNumber,
                'role' => $userRole,
                'userName' => auth()->user()->name,
                'userEmail' => auth()->user()->email,
                'password' => $asamblea->zoom_meeting_password
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generando signature de Zoom', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? null,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }
    
    /**
     * Obtener información de una reunión para un usuario
     */
    public function getMeetingInfo(Asamblea $asamblea): JsonResponse
    {
        try {
            // Verificar que la asamblea tiene Zoom habilitado
            if (!$asamblea->zoom_enabled || !$asamblea->zoom_meeting_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'La videoconferencia no está habilitada para esta asamblea'
                ], 400);
            }
            
            // Verificar permisos del usuario
            $canJoin = $this->zoomService->canUserJoinMeeting($asamblea, auth()->user());
            
            if (!$canJoin['can_join']) {
                return response()->json([
                    'success' => false,
                    'error' => $canJoin['reason'],
                    'available_at' => $canJoin['available_at'] ?? null
                ], 403);
            }
            
            $userRole = $this->zoomService->getUserRole($asamblea, auth()->user());
            
            return response()->json([
                'success' => true,
                'meeting' => [
                    'id' => $asamblea->zoom_meeting_id,
                    'password' => $asamblea->zoom_meeting_password,
                    'topic' => $asamblea->nombre,
                    'start_time' => $asamblea->fecha_inicio->toISOString(),
                    'duration' => $asamblea->fecha_inicio->diffInMinutes($asamblea->fecha_fin),
                    'timezone' => 'America/Bogota',
                    'settings' => $asamblea->zoom_settings
                ],
                'user' => [
                    'role' => $userRole,
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo información de reunión', [
                'error' => $e->getMessage(),
                'asamblea_id' => $asamblea->id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }
    
    /**
     * Verificar el estado de acceso a una reunión
     */
    public function checkAccess(Asamblea $asamblea): JsonResponse
    {
        try {
            $canJoin = $this->zoomService->canUserJoinMeeting($asamblea, auth()->user());
            
            return response()->json([
                'success' => true,
                'can_join' => $canJoin['can_join'],
                'reason' => $canJoin['reason'] ?? null,
                'available_at' => $canJoin['available_at'] ?? null,
                'meeting_status' => $asamblea->getEstadoTemporal(),
                'zoom_enabled' => $asamblea->zoom_enabled
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error verificando acceso a reunión', [
                'error' => $e->getMessage(),
                'asamblea_id' => $asamblea->id,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }
}
