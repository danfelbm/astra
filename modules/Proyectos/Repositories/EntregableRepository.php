<?php

namespace Modules\Proyectos\Repositories;

use Modules\Proyectos\Models\Entregable;
use Modules\Proyectos\Models\Hito;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EntregableRepository
{
    /**
     * Obtiene todos los entregables paginados con filtros.
     */
    public function getAllPaginated(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $query = Entregable::with([
            'hito.proyecto',
            'responsable',
            'usuarios',
            'camposPersonalizados.campoPersonalizado'
        ]);

        // Filtro por búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        // Filtro por hito
        if ($request->filled('hito_id')) {
            $query->where('hito_id', $request->hito_id);
        }

        // Filtro por proyecto (a través del hito)
        if ($request->filled('proyecto_id')) {
            $query->whereHas('hito', function ($q) use ($request) {
                $q->where('proyecto_id', $request->proyecto_id);
            });
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por prioridad
        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        // Filtro por responsable
        if ($request->filled('responsable_id')) {
            $query->where('responsable_id', $request->responsable_id);
        }

        // Filtro para entregables vencidos
        if ($request->boolean('vencidos')) {
            $query->vencidos();
        }

        // Filtro para entregables próximos a vencer
        if ($request->boolean('proximos_vencer')) {
            $query->where('fecha_fin', '<=', now()->addDays(3))
                  ->where('fecha_fin', '>', now())
                  ->whereNotIn('estado', ['completado', 'cancelado']);
        }

        // Ordenamiento
        $sortField = $request->get('sort', 'orden');
        $sortDirection = $request->get('direction', 'asc');

        // Si ordenamos por orden, incluir también el hito
        if ($sortField === 'orden') {
            $query->orderBy('hito_id')
                  ->orderBy('orden', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Obtiene entregables de un hito específico.
     */
    public function getByHito(int $hitoId, array $filters = []): Collection
    {
        $query = Entregable::where('hito_id', $hitoId)
                        ->with([
                            'responsable',
                            'usuarios',
                            'completadoPor',
                            'camposPersonalizados.campoPersonalizado',
                            'etiquetas.categoria'
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

        if (!empty($filters['prioridad'])) {
            $query->where('prioridad', $filters['prioridad']);
        }

        if (!empty($filters['responsable_id'])) {
            $query->where('responsable_id', $filters['responsable_id']);
        }

        return $query->orderBy('orden')->get();
    }

    /**
     * Obtiene entregables del usuario actual.
     */
    public function getMisEntregables(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $query = Entregable::with([
            'hito.proyecto',
            'responsable',
            'usuarios',
            'camposPersonalizados.campoPersonalizado'
        ])->misEntregables();

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

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        // Ordenar por prioridad y fecha de fin
        $query->orderByRaw('CASE prioridad
            WHEN "alta" THEN 1
            WHEN "media" THEN 2
            WHEN "baja" THEN 3
        END')
              ->orderBy('fecha_fin', 'asc');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Encuentra un entregable con sus relaciones.
     */
    public function findWithRelations(int $id): ?Entregable
    {
        return Entregable::with([
            'hito.proyecto',
            'responsable',
            'completadoPor',
            'usuarios',
            'creador',
            'actualizador',
            'camposPersonalizados.campoPersonalizado',
            'etiquetas.categoria'
        ])->find($id);
    }

    /**
     * Crea un nuevo entregable.
     */
    public function create(array $data): Entregable
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        // Si no se especifica orden, ponerlo al final
        if (!isset($data['orden'])) {
            $maxOrden = Entregable::where('hito_id', $data['hito_id'])
                                 ->max('orden') ?? 0;
            $data['orden'] = $maxOrden + 1;
        }

        $entregable = Entregable::create($data);

        // Si hay usuarios asignados, sincronizarlos
        if (isset($data['usuarios'])) {
            $this->asignarUsuarios($entregable, $data['usuarios']);
        }

        // Actualizar el porcentaje del hito
        $entregable->hito->calcularPorcentajeCompletado();

        return $entregable;
    }

    /**
     * Actualiza un entregable existente.
     */
    public function update(Entregable $entregable, array $data): Entregable
    {
        $data['updated_by'] = auth()->id();

        // Guardar el estado anterior para verificar cambios
        $estadoAnterior = $entregable->estado;

        $entregable->update($data);

        // Si hay usuarios asignados, sincronizarlos
        if (isset($data['usuarios'])) {
            $this->asignarUsuarios($entregable, $data['usuarios']);
        }

        // Si cambió el estado, actualizar el porcentaje del hito
        if ($estadoAnterior !== $entregable->estado) {
            $entregable->hito->calcularPorcentajeCompletado();
        }

        return $entregable->fresh();
    }

    /**
     * Elimina un entregable.
     */
    public function delete(Entregable $entregable): bool
    {
        $hito = $entregable->hito;

        // Reordenar los entregables restantes
        Entregable::where('hito_id', $entregable->hito_id)
                  ->where('orden', '>', $entregable->orden)
                  ->decrement('orden');

        $resultado = $entregable->delete();

        // Actualizar el porcentaje del hito
        if ($hito) {
            $hito->calcularPorcentajeCompletado();
        }

        return $resultado;
    }

    /**
     * Marca un entregable como completado.
     */
    public function marcarComoCompletado(Entregable $entregable, int $usuarioId, ?string $notas = null): Entregable
    {
        $entregable->marcarComoCompletado($usuarioId, $notas);

        return $entregable->fresh();
    }

    /**
     * Marca un entregable como en progreso.
     */
    public function marcarComoEnProgreso(Entregable $entregable): Entregable
    {
        $entregable->marcarComoEnProgreso();

        return $entregable->fresh();
    }

    /**
     * Asigna usuarios a un entregable.
     */
    public function asignarUsuarios(Entregable $entregable, array $usuariosConRoles): void
    {
        $entregable->asignarUsuarios($usuariosConRoles);
    }

    /**
     * Obtiene estadísticas de entregables.
     */
    public function getEstadisticas(int $hitoId = null): array
    {
        $query = Entregable::query();

        if ($hitoId) {
            $query->where('hito_id', $hitoId);
        }

        return [
            'total' => (clone $query)->count(),
            'pendientes' => (clone $query)->where('estado', 'pendiente')->count(),
            'en_progreso' => (clone $query)->where('estado', 'en_progreso')->count(),
            'completados' => (clone $query)->where('estado', 'completado')->count(),
            'cancelados' => (clone $query)->where('estado', 'cancelado')->count(),
            'vencidos' => (clone $query)->vencidos()->count(),
            'alta_prioridad' => (clone $query)->where('prioridad', 'alta')
                                              ->whereNotIn('estado', ['completado', 'cancelado'])
                                              ->count(),
        ];
    }

    /**
     * Reordena los entregables de un hito.
     */
    public function reordenar(int $hitoId, array $ordenIds): void
    {
        Entregable::reordenar($hitoId, $ordenIds);
    }

    /**
     * Obtiene entregables con alertas (vencidos o próximos a vencer).
     */
    public function getEntregablesConAlertas(int $hitoId = null): Collection
    {
        $query = Entregable::with(['hito.proyecto', 'responsable']);

        if ($hitoId) {
            $query->where('hito_id', $hitoId);
        }

        return $query->where(function ($q) {
            $q->vencidos()
              ->orWhere(function ($q2) {
                  $q2->where('fecha_fin', '<=', now()->addDays(3))
                     ->where('fecha_fin', '>', now())
                     ->whereNotIn('estado', ['completado', 'cancelado']);
              });
        })->get();
    }

    /**
     * Duplica todos los entregables de un hito a otro.
     */
    public function duplicarEntregablesAHito(Hito $hitoOrigen, Hito $hitoDestino): int
    {
        $entregables = $this->getByHito($hitoOrigen->id);
        $contador = 0;

        foreach ($entregables as $entregable) {
            $nuevoEntregable = $entregable->replicate();
            $nuevoEntregable->hito_id = $hitoDestino->id;
            $nuevoEntregable->estado = 'pendiente';
            $nuevoEntregable->completado_at = null;
            $nuevoEntregable->completado_por = null;
            $nuevoEntregable->notas_completado = null;
            $nuevoEntregable->created_by = auth()->id();
            $nuevoEntregable->updated_by = auth()->id();
            $nuevoEntregable->save();

            // Copiar usuarios asignados
            $usuariosOriginales = $entregable->usuarios()->get();
            foreach ($usuariosOriginales as $usuario) {
                $nuevoEntregable->usuarios()->attach($usuario->id, ['rol' => $usuario->pivot->rol]);
            }

            $contador++;
        }

        return $contador;
    }

    /**
     * Obtiene un resumen de entregables por usuario.
     */
    public function getResumenPorUsuario(int $userId): array
    {
        $query = Entregable::where('responsable_id', $userId)
                          ->orWhereHas('usuarios', function ($q) use ($userId) {
                              $q->where('user_id', $userId);
                          });

        return [
            'total' => (clone $query)->count(),
            'pendientes' => (clone $query)->where('estado', 'pendiente')->count(),
            'en_progreso' => (clone $query)->where('estado', 'en_progreso')->count(),
            'completados' => (clone $query)->where('estado', 'completado')->count(),
            'vencidos' => (clone $query)->vencidos()->count(),
            'alta_prioridad' => (clone $query)->where('prioridad', 'alta')
                                              ->whereNotIn('estado', ['completado', 'cancelado'])
                                              ->count(),
        ];
    }
}