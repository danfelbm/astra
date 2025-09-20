<?php

namespace Modules\Proyectos\Repositories;

use Modules\Proyectos\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProyectoRepository
{
    /**
     * Obtiene todos los proyectos paginados con filtros.
     */
    public function getAllPaginated(Request $request, int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('proyectos.paginacion.proyectos_por_pagina', 15);

        return Proyecto::query()
            ->with(['responsable', 'creador'])
            ->when($request->search, function (Builder $query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            })
            ->when($request->estado, function (Builder $query, $estado) {
                $query->where('estado', $estado);
            })
            ->when($request->prioridad, function (Builder $query, $prioridad) {
                $query->where('prioridad', $prioridad);
            })
            ->when($request->responsable_id, function (Builder $query, $responsableId) {
                $query->where('responsable_id', $responsableId);
            })
            ->when($request->has('activo'), function (Builder $query) use ($request) {
                $query->where('activo', $request->boolean('activo'));
            })
            ->when($request->fecha_desde, function (Builder $query, $fecha) {
                $query->where('fecha_inicio', '>=', $fecha);
            })
            ->when($request->fecha_hasta, function (Builder $query, $fecha) {
                $query->where('fecha_inicio', '<=', $fecha);
            })
            ->when($request->sort_by, function (Builder $query, $sortBy) use ($request) {
                $direction = $request->sort_direction ?? 'asc';
                $query->orderBy($sortBy, $direction);
            }, function (Builder $query) {
                // Orden por defecto
                $query->orderBy('prioridad', 'desc')
                      ->orderBy('fecha_inicio', 'asc')
                      ->orderBy('created_at', 'desc');
            })
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Obtiene un proyecto con todas sus relaciones.
     */
    public function findWithRelations(int $id): ?Proyecto
    {
        return Proyecto::with([
            'responsable',
            'creador',
            'actualizador',
            'camposPersonalizados.campoPersonalizado'
        ])->find($id);
    }

    /**
     * Crea un nuevo proyecto.
     */
    public function create(array $data): Proyecto
    {
        return Proyecto::create($data);
    }

    /**
     * Actualiza un proyecto existente.
     */
    public function update(Proyecto $proyecto, array $data): bool
    {
        return $proyecto->update($data);
    }

    /**
     * Elimina un proyecto.
     */
    public function delete(Proyecto $proyecto): bool
    {
        return $proyecto->delete();
    }

    /**
     * Obtiene proyectos por estado.
     */
    public function getByEstado(string $estado, int $limit = null)
    {
        $query = Proyecto::where('estado', $estado)
                        ->where('activo', true)
                        ->with(['responsable']);

        if ($limit) {
            return $query->limit($limit)->get();
        }

        return $query->get();
    }

    /**
     * Obtiene proyectos vencidos.
     */
    public function getVencidos()
    {
        return Proyecto::vencidos()
                      ->with(['responsable'])
                      ->get();
    }

    /**
     * Obtiene proyectos del usuario actual.
     */
    public function getMisProyectos(int $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return Proyecto::where(function ($query) use ($userId) {
            $query->where('responsable_id', $userId)
                  ->orWhere('created_by', $userId);
        })
        ->with(['responsable'])
        ->orderBy('prioridad', 'desc')
        ->orderBy('fecha_inicio', 'asc')
        ->get();
    }

    /**
     * Obtiene estadísticas de proyectos.
     */
    public function getEstadisticas(): array
    {
        return [
            'total' => Proyecto::count(),
            'activos' => Proyecto::where('activo', true)->count(),
            'por_estado' => Proyecto::selectRaw('estado, count(*) as total')
                                   ->groupBy('estado')
                                   ->pluck('total', 'estado')
                                   ->toArray(),
            'por_prioridad' => Proyecto::selectRaw('prioridad, count(*) as total')
                                      ->groupBy('prioridad')
                                      ->pluck('total', 'prioridad')
                                      ->toArray(),
            'vencidos' => Proyecto::vencidos()->count(),
            'en_progreso' => Proyecto::enProgreso()->count(),
        ];
    }

    /**
     * Busca proyectos por término.
     */
    public function search(string $term, int $limit = 10)
    {
        return Proyecto::where('nombre', 'like', "%{$term}%")
                      ->orWhere('descripcion', 'like', "%{$term}%")
                      ->limit($limit)
                      ->get();
    }
}