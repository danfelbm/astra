<?php

namespace Modules\Proyectos\Repositories;

use Modules\Proyectos\Models\ObligacionContrato;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ObligacionContratoRepository
{
    /**
     * Obtiene todas las obligaciones paginadas con filtros.
     */
    public function getAllPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = ObligacionContrato::with(['contrato', 'padre']);

        // Filtro por contrato
        if ($request->contrato_id) {
            $query->where('contrato_id', $request->contrato_id);
        }

        // Filtro por búsqueda
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        // Filtro por estado
        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        // Filtro por prioridad
        if ($request->prioridad) {
            $query->where('prioridad', $request->prioridad);
        }

        // Filtro por responsable eliminado - campo deprecado

        // Filtro por vencidas
        if ($request->vencidas === 'true' || $request->vencidas === true) {
            $query->vencidas();
        }

        // Filtro por próximas a vencer
        if ($request->proximas_vencer === 'true' || $request->proximas_vencer === true) {
            $query->proximasVencer();
        }

        // Filtro solo raíces (sin padre) si no se especifica un contrato específico
        if (!$request->ver_todas) {
            $query->raiz();
        }

        // Ordenamiento
        $sortField = $request->sort_field ?? 'orden';
        $sortDirection = $request->sort_direction ?? 'asc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Obtiene el árbol completo de obligaciones para un contrato.
     */
    public function getArbolPorContrato(int $contratoId): Collection
    {
        return ObligacionContrato::where('contrato_id', $contratoId)
                                ->whereNull('parent_id')
                                ->with(['descendientes'])
                                ->orderBy('orden')
                                ->get();
    }

    /**
     * Obtiene las obligaciones del usuario actual.
     */
    public function getMisObligaciones(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = ObligacionContrato::with(['contrato', 'padre'])
                                  ->misObligaciones();

        // Aplicar filtros similares
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->prioridad) {
            $query->where('prioridad', $request->prioridad);
        }

        // Ordenar por prioridad y fecha de vencimiento
        $query->orderByRaw("FIELD(prioridad, 'alta', 'media', 'baja')")
              ->orderBy('fecha_vencimiento', 'asc')
              ->orderBy('orden', 'asc');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Busca una obligación con sus relaciones.
     */
    public function findWithRelations(int $id): ?ObligacionContrato
    {
        return ObligacionContrato::with([
            'contrato',
            'padre',
            'hijos',
            'descendientes',
            'creador',
            'actualizador'
        ])->find($id);
    }

    /**
     * Crea una nueva obligación.
     */
    public function create(array $data): ObligacionContrato
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return ObligacionContrato::create($data);
    }

    /**
     * Actualiza una obligación existente.
     */
    public function update(ObligacionContrato $obligacion, array $data): bool
    {
        $data['updated_by'] = auth()->id();

        return $obligacion->update($data);
    }

    /**
     * Elimina una obligación y sus hijos.
     */
    public function delete(ObligacionContrato $obligacion): bool
    {
        // Las obligaciones hijas se eliminarán en cascada por la FK
        return $obligacion->delete();
    }

    /**
     * Obtiene estadísticas de obligaciones.
     */
    public function getEstadisticas(?int $contratoId = null): array
    {
        $query = ObligacionContrato::query();

        if ($contratoId) {
            $query->where('contrato_id', $contratoId);
        }

        return [
            'total' => $query->count(),
            'pendientes' => (clone $query)->pendientes()->count(),
            'en_progreso' => (clone $query)->where('estado', 'en_progreso')->count(),
            'cumplidas' => (clone $query)->cumplidas()->count(),
            'vencidas' => (clone $query)->vencidas()->count(),
            'proximas_vencer' => (clone $query)->proximasVencer()->count(),
            'alta_prioridad' => (clone $query)->altaPrioridad()->whereNotIn('estado', ['cumplida', 'cancelada'])->count(),
            'porcentaje_cumplimiento' => $query->count() > 0
                ? round((clone $query)->cumplidas()->count() / $query->count() * 100)
                : 0
        ];
    }

    /**
     * Obtiene estadísticas por responsable.
     */
    public function getEstadisticasPorResponsable(?int $contratoId = null): Collection
    {
        $query = ObligacionContrato::query()
                                  ->select('responsable_id')
                                  ->selectRaw('COUNT(*) as total')
                                  ->selectRaw('COUNT(CASE WHEN estado = "pendiente" THEN 1 END) as pendientes')
                                  ->selectRaw('COUNT(CASE WHEN estado = "en_progreso" THEN 1 END) as en_progreso')
                                  ->selectRaw('COUNT(CASE WHEN estado = "cumplida" THEN 1 END) as cumplidas')
                                  ->selectRaw('COUNT(CASE WHEN estado = "vencida" OR (fecha_vencimiento < NOW() AND estado NOT IN ("cumplida", "cancelada")) THEN 1 END) as vencidas')
                                  ->groupBy('responsable_id');

        if ($contratoId) {
            $query->where('contrato_id', $contratoId);
        }

        return $query->get();
    }

    /**
     * Obtiene obligaciones vencidas o próximas a vencer.
     */
    public function getObligacionesCriticas(?int $contratoId = null): Collection
    {
        $query = ObligacionContrato::with(['contrato']);

        if ($contratoId) {
            $query->where('contrato_id', $contratoId);
        }

        return $query->where(function ($q) {
                        $q->vencidas()
                          ->orWhere(function ($q2) {
                              $q2->proximasVencer(3); // 3 días
                          });
                    })
                    ->orderByRaw("FIELD(prioridad, 'alta', 'media', 'baja')")
                    ->orderBy('fecha_vencimiento', 'asc')
                    ->get();
    }

    /**
     * Mueve una obligación a otra posición o padre.
     */
    public function mover(ObligacionContrato $obligacion, ?int $nuevoParentId, int $nuevoOrden): bool
    {
        // Verificar que no se está moviendo a un hijo propio
        if ($nuevoParentId) {
            $nuevoPadre = ObligacionContrato::find($nuevoParentId);
            if ($nuevoPadre && $this->esDescendienteDe($nuevoPadre, $obligacion)) {
                return false; // No permitir ciclos
            }
        }

        // Actualizar padre y orden
        $obligacion->parent_id = $nuevoParentId;
        $obligacion->orden = $nuevoOrden;

        // El modelo actualizará automáticamente el path y nivel
        return $obligacion->save();
    }

    /**
     * Verifica si una obligación es descendiente de otra.
     */
    protected function esDescendienteDe(ObligacionContrato $posibleDescendiente, ObligacionContrato $obligacion): bool
    {
        if (!$obligacion->path) {
            return false;
        }

        $pathObligacion = $obligacion->path . '.' . $obligacion->id;
        $pathDescendiente = $posibleDescendiente->path;

        return $pathDescendiente && str_starts_with($pathDescendiente, $pathObligacion);
    }

    /**
     * Duplica una estructura completa de obligaciones.
     */
    public function duplicarEstructura(int $contratoOrigenId, int $contratoDestinoId): bool
    {
        $obligacionesRaiz = ObligacionContrato::where('contrato_id', $contratoOrigenId)
                                             ->whereNull('parent_id')
                                             ->orderBy('orden')
                                             ->get();

        foreach ($obligacionesRaiz as $obligacion) {
            $this->duplicarObligacionConHijos($obligacion, $contratoDestinoId, null);
        }

        return true;
    }

    /**
     * Duplica una obligación con todos sus hijos (recursivo).
     */
    protected function duplicarObligacionConHijos(ObligacionContrato $obligacion, int $nuevoContratoId, ?int $nuevoParentId): ObligacionContrato
    {
        $nuevaObligacion = $obligacion->replicate();
        $nuevaObligacion->contrato_id = $nuevoContratoId;
        $nuevaObligacion->parent_id = $nuevoParentId;
        // Campos de estado eliminados - deprecados
        $nuevaObligacion->created_by = auth()->id();
        $nuevaObligacion->updated_by = auth()->id();
        $nuevaObligacion->save();

        // Duplicar hijos
        foreach ($obligacion->hijos as $hijo) {
            $this->duplicarObligacionConHijos($hijo, $nuevoContratoId, $nuevaObligacion->id);
        }

        return $nuevaObligacion;
    }

    /**
     * Actualiza masivamente el estado de varias obligaciones.
     */
    public function actualizarEstadoMasivo(array $ids, string $nuevoEstado): int
    {
        return ObligacionContrato::whereIn('id', $ids)
                                ->update([
                                    'estado' => $nuevoEstado,
                                    'updated_by' => auth()->id()
                                ]);
    }

    /**
     * Busca obligaciones para autocompletar.
     */
    public function buscarParaAutocompletar(string $termino, ?int $contratoId = null, int $limite = 10): Collection
    {
        $query = ObligacionContrato::select('id', 'titulo', 'parent_id', 'nivel')
                                  ->where('titulo', 'like', "%{$termino}%");

        if ($contratoId) {
            $query->where('contrato_id', $contratoId);
        }

        return $query->limit($limite)->get();
    }

    /**
     * Obtiene la timeline de obligaciones para un contrato.
     */
    public function getTimelinePorContrato(int $contratoId): Collection
    {
        return ObligacionContrato::where('contrato_id', $contratoId)
                                ->whereNotNull('fecha_vencimiento')
                                ->with(['padre'])
                                ->orderBy('fecha_vencimiento', 'asc')
                                ->get()
                                ->groupBy(function ($obligacion) {
                                    return $obligacion->fecha_vencimiento->format('Y-m');
                                });
    }
}