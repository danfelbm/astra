<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formulario;
use App\Models\FormularioRespuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FormularioController extends Controller
{
    /**
     * Autoguardar respuesta nueva
     */
    public function autosave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'formulario_id' => 'required|exists:formularios,id',
            'respuestas' => 'required|array',
            'nombre_visitante' => 'nullable|string|max:255',
            'email_visitante' => 'nullable|email|max:255',
            'telefono_visitante' => 'nullable|string|max:20',
            'documento_visitante' => 'nullable|string|max:50',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $formulario = Formulario::findOrFail($request->formulario_id);
        
        // Verificar que el formulario esté disponible
        if (!$formulario->estaDisponible()) {
            return response()->json([
                'success' => false,
                'message' => 'Este formulario no está disponible.',
            ], 403);
        }
        
        $usuario = Auth::user();
        
        // Verificar permisos
        if (!$formulario->puedeSerLlenadoPor($usuario)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para llenar este formulario.',
            ], 403);
        }
        
        DB::beginTransaction();
        try {
            // Buscar respuesta existente en borrador
            $respuesta = null;
            
            if ($usuario) {
                $respuesta = FormularioRespuesta::where('formulario_id', $formulario->id)
                    ->where('usuario_id', $usuario->id)
                    ->where('estado', 'borrador')
                    ->first();
            }
            
            if ($respuesta) {
                // Actualizar respuesta existente
                $respuesta->update([
                    'respuestas' => $request->respuestas,
                    'metadata' => array_merge($respuesta->metadata ?? [], [
                        'ultimo_autoguardado' => now()->toIso8601String(),
                        'ip' => $request->ip(),
                    ]),
                ]);
            } else {
                // Crear nueva respuesta en borrador
                $datos = [
                    'formulario_id' => $formulario->id,
                    'respuestas' => $request->respuestas,
                    'estado' => 'borrador',
                    'iniciado_en' => now(),
                    'metadata' => [
                        'primer_autoguardado' => now()->toIso8601String(),
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ],
                ];
                
                if ($usuario) {
                    $datos['usuario_id'] = $usuario->id;
                } else {
                    // Guardar datos del visitante si están disponibles
                    $datos['nombre_visitante'] = $request->nombre_visitante;
                    $datos['email_visitante'] = $request->email_visitante;
                    $datos['telefono_visitante'] = $request->telefono_visitante;
                    $datos['documento_visitante'] = $request->documento_visitante;
                }
                
                $respuesta = FormularioRespuesta::create($datos);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Respuesta guardada automáticamente.',
                'data' => [
                    'respuesta_id' => $respuesta->id,
                    'ultimo_guardado' => now()->toIso8601String(),
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la respuesta.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Autoguardar respuesta existente
     */
    public function autosaveExisting(FormularioRespuesta $respuesta, Request $request)
    {
        // Verificar que el usuario sea el dueño de la respuesta
        $usuario = Auth::user();
        
        if ($respuesta->usuario_id !== $usuario->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para editar esta respuesta.',
            ], 403);
        }
        
        // Verificar que la respuesta esté en borrador
        if ($respuesta->estado !== 'borrador') {
            return response()->json([
                'success' => false,
                'message' => 'Esta respuesta ya fue enviada y no puede ser editada.',
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'respuestas' => 'required|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // Verificar que el formulario siga disponible
        if (!$respuesta->formulario->estaDisponible()) {
            return response()->json([
                'success' => false,
                'message' => 'Este formulario ya no está disponible.',
            ], 403);
        }
        
        DB::beginTransaction();
        try {
            $respuesta->update([
                'respuestas' => $request->respuestas,
                'metadata' => array_merge($respuesta->metadata ?? [], [
                    'ultimo_autoguardado' => now()->toIso8601String(),
                    'ip' => $request->ip(),
                ]),
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Respuesta actualizada automáticamente.',
                'data' => [
                    'respuesta_id' => $respuesta->id,
                    'ultimo_guardado' => now()->toIso8601String(),
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la respuesta.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    
    /**
     * Recuperar borrador guardado
     */
    public function getDraft(Request $request, $formularioId)
    {
        $usuario = Auth::user();
        
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Debes estar autenticado para recuperar borradores.',
            ], 401);
        }
        
        $formulario = Formulario::findOrFail($formularioId);
        
        $borrador = FormularioRespuesta::where('formulario_id', $formulario->id)
            ->where('usuario_id', $usuario->id)
            ->where('estado', 'borrador')
            ->first();
            
        if (!$borrador) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un borrador para este formulario.',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'respuesta_id' => $borrador->id,
                'respuestas' => $borrador->respuestas,
                'ultimo_guardado' => $borrador->updated_at->toIso8601String(),
            ],
        ]);
    }
    
    /**
     * Eliminar borrador
     */
    public function deleteDraft(Request $request, $formularioId)
    {
        $usuario = Auth::user();
        
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Debes estar autenticado para eliminar borradores.',
            ], 401);
        }
        
        $formulario = Formulario::findOrFail($formularioId);
        
        $borrador = FormularioRespuesta::where('formulario_id', $formulario->id)
            ->where('usuario_id', $usuario->id)
            ->where('estado', 'borrador')
            ->first();
            
        if (!$borrador) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un borrador para este formulario.',
            ], 404);
        }
        
        DB::beginTransaction();
        try {
            $borrador->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Borrador eliminado exitosamente.',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el borrador.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}