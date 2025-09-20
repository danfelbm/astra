<?php

namespace Modules\Proyectos\Http\Controllers\Guest;

use Modules\Core\Http\Controllers\Base\GuestController;
use Modules\Proyectos\Models\Proyecto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProyectoPublicoController extends GuestController
{
    /**
     * Muestra la lista de proyectos públicos.
     */
    public function index(Request $request): Response
    {
        // Solo mostrar proyectos activos y en progreso o completados
        $proyectos = Proyecto::query()
            ->where('activo', true)
            ->whereIn('estado', ['en_progreso', 'completado'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            })
            ->when($request->estado, function ($query, $estado) {
                $query->where('estado', $estado);
            })
            ->with(['responsable:id,name'])
            ->select([
                'id',
                'nombre',
                'descripcion',
                'estado',
                'prioridad',
                'fecha_inicio',
                'fecha_fin',
                'responsable_id',
                'created_at'
            ])
            ->orderBy('prioridad', 'desc')
            ->orderBy('fecha_inicio', 'desc')
            ->paginate(12);

        return Inertia::render('Modules/Proyectos/Guest/Proyectos/Index', [
            'proyectos' => $proyectos,
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    /**
     * Muestra los detalles de un proyecto público.
     */
    public function show($id): Response
    {
        $proyecto = Proyecto::where('activo', true)
            ->whereIn('estado', ['en_progreso', 'completado'])
            ->with(['responsable:id,name'])
            ->select([
                'id',
                'nombre',
                'descripcion',
                'estado',
                'prioridad',
                'fecha_inicio',
                'fecha_fin',
                'responsable_id',
                'created_at'
            ])
            ->findOrFail($id);

        // Obtener solo los campos personalizados que sean públicos
        $camposPublicos = $proyecto->camposPersonalizados()
            ->whereHas('campoPersonalizado', function ($query) {
                $query->where('activo', true);
            })
            ->with('campoPersonalizado:id,nombre,tipo')
            ->get()
            ->map(function ($valor) {
                return [
                    'nombre' => $valor->campoPersonalizado->nombre,
                    'tipo' => $valor->campoPersonalizado->tipo,
                    'valor' => $valor->valor_formateado
                ];
            });

        return Inertia::render('Modules/Proyectos/Guest/Proyectos/Show', [
            'proyecto' => $proyecto,
            'camposPublicos' => $camposPublicos,
        ]);
    }
}