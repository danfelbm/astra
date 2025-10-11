<?php

namespace Modules\Proyectos\Repositories;

use Modules\Proyectos\Models\Hito;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class HitoRepository
{
    /**
     * Obtiene todos los hitos paginados con filtros.
     */
    public function getAllPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Hito::with([
            'proyecto',
            'responsable',
            'entregables',
            'camposPersonalizados.campoPersonalizado',
            'parent',
            'children'
        ]);

        // Filtro por búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        // Filtro por proyecto
        if ($request->filled('proyecto_id')) {
            $query->where('proyecto_id', $request->proyecto_id);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por responsable
        if ($request->filled('responsable_id')) {
            $query->where('responsable_id', $request->responsable_id);
        }

        // Filtro para hitos vencidos
        if ($request->boolean('vencidos')) {
            $query->vencidos();
        }

        // Filtro para hitos próximos a vencer
        if ($request->boolean('proximos_vencer')) {
            $query->proximosVencer();
        }

        // Ordenamiento
        $sortField = $request->get('sort', 'orden');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Obtiene hitos de un proyecto específico.
     */
    public function getByProyecto(int $proyectoId, array $filters = []): Collection
    {
        $query = Hito::where('proyecto_id', $proyectoId)
                   ->with([
                       'responsable',
                       'entregables',
                       'camposPersonalizados.campoPersonalizado',
                       'parent',
                       'children'
                   ]);

        // Aplicar filtros si existen
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['responsable_id'])) {
            $query->where('responsable_id', $filters['responsable_id']);
        }

        return $query->orderBy('orden')->get();
    }

    /**
     * Obtiene hitos del usuario actual.
     */
    public function getMisHitos(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Hito::with([
            'proyecto',
            'responsable',
            'entregables',
            'camposPersonalizados.campoPersonalizado',
            'parent',
            'children'
        ])->misHitos();

        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Ordenar por fecha de fin más cercana
        $query->orderByRaw('CASE
            WHEN estado IN ("completado", "cancelado") THEN 1
            ELSE 0
        END')
              ->orderBy('fecha_fin', 'asc')
              ->orderBy('orden', 'asc');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Encuentra un hito con sus relaciones.
     */
    public function findWithRelations(int $id): ?Hito
    {
        return Hito::with([
            'proyecto',
            'responsable',
            'entregables' => function ($query) {
                $query->with(['responsable', 'usuarios'])
                      ->orderBy('orden');
            }
        ])->find($id);
    }

    /**
     * Crea un nuevo hito.
     */
    public function create(array $data): Hito
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        // Si no se especifica orden, ponerlo al final
        if (!isset($data['orden'])) {
            $maxOrden = Hito::where('proyecto_id', $data['proyecto_id'])
                           ->max('orden') ?? 0;
            $data['orden'] = $maxOrden + 1;
        }

        return Hito::create($data);
    }

    /**
     * Actualiza un hito existente.
     */
    public function update(Hito $hito, array $data): Hito
    {
        $data['updated_by'] = auth()->id();
        $hito->update($data);

        return $hito->fresh();
    }

    /**
     * Elimina un hito.
     */
    public function delete(Hito $hito): bool
    {
        // Reordenar los hitos restantes
        Hito::where('proyecto_id', $hito->proyecto_id)
            ->where('orden', '>', $hito->orden)
            ->decrement('orden');

        return $hito->delete();
    }

    /**
     * Obtiene estadísticas de hitos.
     */
    public function getEstadisticas(int $proyectoId = null): array
    {
        $query = Hito::query();

        if ($proyectoId) {
            $query->where('proyecto_id', $proyectoId);
        }

        return [
            'total' => (clone $query)->count(),
            'pendientes' => (clone $query)->where('estado', 'pendiente')->count(),
            'en_progreso' => (clone $query)->where('estado', 'en_progreso')->count(),
            'completados' => (clone $query)->where('estado', 'completado')->count(),
            'cancelados' => (clone $query)->where('estado', 'cancelado')->count(),
            'vencidos' => (clone $query)->vencidos()->count(),
            'proximos_vencer' => (clone $query)->proximosVencer()->count(),
        ];
    }

    /**
     * Duplica un hito con sus entregables.
     */
    public function duplicar(Hito $hito, array $datosNuevos = []): Hito
    {
        $nuevoNombre = $datosNuevos['nombre'] ?? $hito->nombre . ' (Copia)';
        return $hito->duplicar($nuevoNombre);
    }

    /**
     * Reordena los hitos de un proyecto.
     */
    public function reordenar(int $proyectoId, array $ordenIds): void
    {
        Hito::reordenar($proyectoId, $ordenIds);
    }

    /**
     * Obtiene hitos con alertas (vencidos o próximos a vencer).
     */
    public function getHitosConAlertas(int $proyectoId = null): Collection
    {
        $query = Hito::with(['proyecto', 'responsable']);

        if ($proyectoId) {
            $query->where('proyecto_id', $proyectoId);
        }

        return $query->where(function ($q) {
            $q->vencidos()
              ->orWhere(function ($q2) {
                  $q2->proximosVencer();
              });
        })->get();
    }

    /**
     * Obtiene el timeline de hitos para visualización.
     */
    public function getTimelineData(int $proyectoId): array
    {
        return Hito::where('proyecto_id', $proyectoId)
                   ->with(['responsable', 'entregables'])
                   ->orderBy('fecha_inicio')
                   ->get()
                   ->map(function ($hito) {
                       return [
                           'id' => $hito->id,
                           'nombre' => $hito->nombre,
                           'descripcion' => $hito->descripcion,
                           'fecha_inicio' => $hito->fecha_inicio?->format('Y-m-d'),
                           'fecha_fin' => $hito->fecha_fin?->format('Y-m-d'),
                           'estado' => $hito->estado,
                           'estado_label' => $hito->estado_label,
                           'estado_color' => $hito->estado_color,
                           'porcentaje_completado' => $hito->porcentaje_completado,
                           'responsable' => $hito->responsable?->name,
                           'total_entregables' => $hito->total_entregables,
                           'entregables_completados' => $hito->entregables_completados,
                           'esta_vencido' => $hito->esta_vencido,
                           'esta_proximo_vencer' => $hito->esta_proximo_vencer
                       ];
                   })->toArray();
    }

    /**
     * Obtiene hitos raíz de un proyecto (sin padre).
     */
    public function getRaices(int $proyectoId): Collection
    {
        return Hito::where('proyecto_id', $proyectoId)
                   ->raices()
                   ->with([
                       'responsable',
                       'entregables',
                       'camposPersonalizados.campoPersonalizado',
                       'children.children' // Eager load hasta 2 niveles
                   ])
                   ->orderBy('orden')
                   ->get();
    }

    /**
     * Obtiene la estructura jerárquica completa de hitos de un proyecto.
     */
    public function getArbolJerarquico(int $proyectoId): array
    {
        // Obtener todos los hitos del proyecto con relaciones
        $hitos = Hito::where('proyecto_id', $proyectoId)
                     ->with([
                         'responsable',
                         'entregables',
                         'camposPersonalizados.campoPersonalizado',
                         'children'
                     ])
                     ->orderBy('orden')
                     ->get();

        // Construir árbol jerárquico
        $arbol = [];
        $hitosIndexados = [];

        // Indexar todos los hitos por ID
        foreach ($hitos as $hito) {
            $hitosIndexados[$hito->id] = [
                'hito' => $hito,
                'children' => []
            ];
        }

        // Construir estructura jerárquica
        foreach ($hitos as $hito) {
            if ($hito->parent_id === null) {
                // Es un hito raíz
                $arbol[] = &$hitosIndexados[$hito->id];
            } else {
                // Es un hijo, agregarlo a su padre
                if (isset($hitosIndexados[$hito->parent_id])) {
                    $hitosIndexados[$hito->parent_id]['children'][] = &$hitosIndexados[$hito->id];
                }
            }
        }

        return $arbol;
    }

    /**
     * Obtiene un hito con toda su jerarquía (ancestros y descendientes).
     */
    public function findWithJerarquia(int $hitoId): ?Hito
    {
        $hito = Hito::with([
            'responsable',
            'entregables',
            'camposPersonalizados.campoPersonalizado',
            'parent.parent.parent', // Hasta 3 niveles de ancestros
            'children.children.children' // Hasta 3 niveles de descendientes
        ])->find($hitoId);

        return $hito;
    }
}