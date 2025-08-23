<?php

namespace App\Http\Controllers;

use App\Models\Asamblea;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FrontendAsambleaController extends Controller
{
    /**
     * Mostrar página de consulta de participantes
     */
    public function consultaParticipantes(Request $request): Response
    {
        // Obtener asambleas con consulta pública habilitada
        $asambleas = Asamblea::query()
            ->where('activo', true)
            ->where('public_participants_enabled', true)
            ->with(['territorio', 'departamento', 'municipio', 'localidad'])
            ->withCount('participantes')
            ->orderBy('fecha_inicio', 'desc')
            ->paginate(20)
            ->through(function ($asamblea) {
                return [
                    'id' => $asamblea->id,
                    'nombre' => $asamblea->nombre,
                    'descripcion' => $asamblea->descripcion,
                    'tipo' => $asamblea->tipo,
                    'tipo_label' => $asamblea->getTipoLabel(),
                    'estado' => $asamblea->getEstadoTemporal(),
                    'estado_label' => $asamblea->getEstadoLabel(),
                    'estado_color' => $asamblea->getEstadoColor(),
                    'fecha_inicio' => $asamblea->fecha_inicio,
                    'fecha_fin' => $asamblea->fecha_fin,
                    'lugar' => $asamblea->lugar,
                    'ubicacion_completa' => $asamblea->getUbicacionCompleta(),
                    'participantes_count' => $asamblea->participantes_count,
                    'public_participants_mode' => $asamblea->public_participants_mode,
                    'public_participants_mode_label' => $asamblea->public_participants_mode === 'list' 
                        ? 'Listado completo' 
                        : 'Solo búsqueda',
                ];
            });

        return Inertia::render('frontend/asambleas/ConsultaParticipantes', [
            'asambleas' => $asambleas,
        ]);
    }
}