<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Convocatoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConvocatoriaController extends Controller
{
    /**
     * Obtener convocatorias disponibles para el usuario actual
     * Este endpoint es usado por el ConvocatoriaSelector.vue
     */
    public function disponibles(Request $request)
    {
        $usuario = Auth::user();
        
        // Construir query base
        $query = Convocatoria::with(['cargo', 'periodoElectoral', 'territorio', 'departamento', 'municipio', 'localidad'])
            ->activas();

        // Si se solicita incluir futuras
        if ($request->boolean('incluir_futuras', false)) {
            $query->disponibles(); // Este scope incluye abiertas y futuras
        } else {
            $query->abiertas(); // Solo abiertas
        }

        // Obtener todas las convocatorias
        $convocatorias = $query->get();

        // Filtrar por ubicación del usuario si se solicita
        if ($request->boolean('filtrar_ubicacion', true)) {
            $convocatorias = $convocatorias->filter(function ($convocatoria) use ($usuario) {
                return $convocatoria->esDisponibleParaUsuario($usuario->id);
            });
        }

        // Mapear para la respuesta
        $resultado = $convocatorias->map(function ($convocatoria) use ($usuario) {
            // Verificar si el usuario ya tiene postulación
            $tienePostulacion = $convocatoria->postulaciones()
                ->where('user_id', $usuario->id)
                ->exists();

            return [
                'id' => $convocatoria->id,
                'nombre' => $convocatoria->nombre,
                'descripcion' => $convocatoria->descripcion,
                'cargo' => [
                    'id' => $convocatoria->cargo->id,
                    'nombre' => $convocatoria->cargo->nombre,
                    'ruta_jerarquica' => $convocatoria->cargo->ruta_jerarquica,
                ],
                'periodo_electoral' => [
                    'id' => $convocatoria->periodoElectoral->id,
                    'nombre' => $convocatoria->periodoElectoral->nombre,
                ],
                'fecha_apertura' => $convocatoria->fecha_apertura->toISOString(),
                'fecha_cierre' => $convocatoria->fecha_cierre->toISOString(),
                'estado_temporal' => $convocatoria->getEstadoTemporal(),
                'ubicacion' => $convocatoria->getUbicacionTexto(),
                'numero_postulaciones' => $convocatoria->getNumeroPostulaciones(),
                'territorio_id' => $convocatoria->territorio_id,
                'departamento_id' => $convocatoria->departamento_id,
                'municipio_id' => $convocatoria->municipio_id,
                'localidad_id' => $convocatoria->localidad_id,
                'tiene_postulacion' => $tienePostulacion,
                'disponible' => !$tienePostulacion, // No disponible si ya tiene postulación
            ];
        })->filter(function ($convocatoria) {
            // Filtrar las que ya tienen postulación del usuario
            return $convocatoria['disponible'];
        })->values();

        return response()->json($resultado);
    }

    /**
     * Verificar si una convocatoria específica está disponible para el usuario
     */
    public function verificarDisponibilidad(Convocatoria $convocatoria)
    {
        $usuario = Auth::user();
        
        $disponible = $convocatoria->esDisponibleParaUsuario($usuario->id);
        
        return response()->json([
            'disponible' => $disponible,
            'convocatoria' => [
                'id' => $convocatoria->id,
                'nombre' => $convocatoria->nombre,
                'estado_temporal' => $convocatoria->getEstadoTemporal(),
            ]
        ]);
    }
}