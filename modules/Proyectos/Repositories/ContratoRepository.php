<?php

namespace Modules\Proyectos\Repositories;

use Modules\Proyectos\Models\Contrato;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ContratoRepository
{
    /**
     * Obtiene todos los contratos paginados con filtros.
     */
    public function getAllPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return Contrato::query()
            ->with(['proyecto', 'responsable'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%")
                      ->orWhere('contraparte_nombre', 'like', "%{$search}%")
                      ->orWhereHas('proyecto', function ($q2) use ($search) {
                          $q2->where('nombre', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->proyecto_id, function ($query, $proyectoId) {
                $query->where('proyecto_id', $proyectoId);
            })
            ->when($request->estado, function ($query, $estado) {
                $query->where('estado', $estado);
            })
            ->when($request->tipo, function ($query, $tipo) {
                $query->where('tipo', $tipo);
            })
            ->when($request->responsable_id, function ($query, $responsableId) {
                $query->where('responsable_id', $responsableId);
            })
            ->when($request->fecha_inicio, function ($query, $fecha) {
                $query->where('fecha_inicio', '>=', $fecha);
            })
            ->when($request->fecha_fin, function ($query, $fecha) {
                $query->where('fecha_fin', '<=', $fecha);
            })
            ->when($request->vencidos === 'true', function ($query) {
                $query->vencidos();
            })
            ->when($request->proximos_vencer === 'true', function ($query) {
                $query->proximosVencer();
            })
            ->when($request->sortBy, function ($query, $sortBy) use ($request) {
                $sortDirection = $request->sortDirection ?? 'asc';
                if (in_array($sortBy, ['nombre', 'fecha_inicio', 'fecha_fin', 'monto_total', 'estado', 'tipo'])) {
                    $query->orderBy($sortBy, $sortDirection);
                }
            }, function ($query) {
                $query->latest();
            })
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Encuentra un contrato con sus relaciones.
     */
    public function findWithRelations(int $id): ?Contrato
    {
        return Contrato::with([
            'proyecto' => function ($query) {
                $query->with(['responsable', 'etiquetas']);
            },
            'responsable',
            'contraparteUser',
            'participantes',
            'creador',
            'actualizador',
            'camposPersonalizados' => function ($query) {
                $query->with('campoPersonalizado');
            }
        ])->find($id);
    }

    /**
     * Obtiene contratos por proyecto.
     */
    public function getByProyecto(int $proyectoId): Collection
    {
        return Contrato::where('proyecto_id', $proyectoId)
            ->with(['responsable'])
            ->orderBy('fecha_inicio', 'desc')
            ->get();
    }

    /**
     * Obtiene contratos por proyecto paginados.
     */
    public function getByProyectoPaginated(int $proyectoId, int $perPage = 10): LengthAwarePaginator
    {
        return Contrato::where('proyecto_id', $proyectoId)
            ->with(['responsable'])
            ->orderBy('fecha_inicio', 'desc')
            ->paginate($perPage);
    }

    /**
     * Obtiene contratos vencidos próximamente.
     */
    public function getVencidosProximos(int $dias = 30): Collection
    {
        return Contrato::proximosVencer($dias)
            ->with(['proyecto', 'responsable'])
            ->orderBy('fecha_fin')
            ->get();
    }

    /**
     * Obtiene contratos vencidos.
     */
    public function getVencidos(): Collection
    {
        return Contrato::vencidos()
            ->with(['proyecto', 'responsable'])
            ->orderBy('fecha_fin', 'desc')
            ->get();
    }

    /**
     * Obtiene estadísticas de contratos.
     */
    public function getEstadisticas(?int $proyectoId = null): array
    {
        $query = Contrato::query();

        if ($proyectoId) {
            $query->where('proyecto_id', $proyectoId);
        }

        $total = $query->count();
        $activos = (clone $query)->where('estado', 'activo')->count();
        $finalizados = (clone $query)->where('estado', 'finalizado')->count();
        $cancelados = (clone $query)->where('estado', 'cancelado')->count();
        $borradores = (clone $query)->where('estado', 'borrador')->count();
        $vencidos = (clone $query)->vencidos()->count();
        $proximosVencer = (clone $query)->proximosVencer()->count();

        $montoTotal = (clone $query)->whereIn('estado', ['activo', 'finalizado'])->sum('monto_total');
        $montoActivos = (clone $query)->where('estado', 'activo')->sum('monto_total');

        $porTipo = (clone $query)
            ->selectRaw('tipo, count(*) as total, sum(monto_total) as monto')
            ->groupBy('tipo')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->tipo => [
                    'total' => $item->total,
                    'monto' => $item->monto
                ]];
            })
            ->toArray();

        return [
            'total' => $total,
            'por_estado' => [
                'activo' => $activos,
                'finalizado' => $finalizados,
                'cancelado' => $cancelados,
                'borrador' => $borradores,
            ],
            'vencidos' => $vencidos,
            'proximos_vencer' => $proximosVencer,
            'monto_total' => $montoTotal,
            'monto_activos' => $montoActivos,
            'por_tipo' => $porTipo,
        ];
    }

    /**
     * Crea un nuevo contrato.
     */
    public function create(array $data): Contrato
    {
        return Contrato::create($data);
    }

    /**
     * Actualiza un contrato.
     */
    public function update(Contrato $contrato, array $data): bool
    {
        return $contrato->update($data);
    }

    /**
     * Elimina un contrato.
     */
    public function delete(Contrato $contrato): bool
    {
        return $contrato->delete();
    }

    /**
     * Busca contratos por término.
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Contrato::query()
            ->where(function ($query) use ($term) {
                $query->where('nombre', 'like', "%{$term}%")
                      ->orWhere('descripcion', 'like', "%{$term}%")
                      ->orWhere('contraparte_nombre', 'like', "%{$term}%");
            })
            ->with(['proyecto', 'responsable'])
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene contratos del usuario actual.
     */
    public function getMisContratos(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $userId = auth()->id();

        return Contrato::query()
            ->where(function ($query) use ($userId) {
                $query->where('responsable_id', $userId)
                      ->orWhereHas('proyecto', function ($q) use ($userId) {
                          $q->where('responsable_id', $userId);
                      });
            })
            ->with(['proyecto', 'responsable'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            })
            ->when($request->estado, function ($query, $estado) {
                $query->where('estado', $estado);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Obtiene contratos por rango de fechas.
     */
    public function getByDateRange($fechaInicio, $fechaFin): Collection
    {
        return Contrato::entreFechas($fechaInicio, $fechaFin)
            ->with(['proyecto', 'responsable'])
            ->orderBy('fecha_inicio')
            ->get();
    }

    /**
     * Obtiene contratos con montos mayores a un valor.
     */
    public function getByMontoMinimo(float $monto): Collection
    {
        return Contrato::montoMayorA($monto)
            ->with(['proyecto', 'responsable'])
            ->orderByDesc('monto_total')
            ->get();
    }

    /**
     * Verifica si existe un contrato con el nombre dado en un proyecto.
     */
    public function existeNombreEnProyecto(string $nombre, int $proyectoId, ?int $excludeId = null): bool
    {
        $query = Contrato::where('nombre', $nombre)
            ->where('proyecto_id', $proyectoId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}