<?php

namespace Modules\Votaciones\Repositories;

use Modules\Votaciones\Models\Votacion;
use Modules\Votaciones\Models\Categoria;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class VotacionRepository
{
    use HasAdvancedFilters;

    /**
     * Obtener votaciones paginadas con filtros avanzados
     */
    public function getAllPaginated(Request $request, int $perPage = 10): LengthAwarePaginator
    {
        $query = Votacion::with(['categoria'])->withCount('votantes');

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'titulo',
            'descripcion',
            'categoria_id',
            'estado',
            'fecha_inicio',
            'fecha_fin',
            'fecha_publicacion_resultados',
            'limite_censo',
            'resultados_publicos',
            'created_at',
            'updated_at',
            'votantes_count'
        ];

        // Campos para búsqueda rápida
        $quickSearchFields = ['titulo', 'descripcion'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);

        // Mantener compatibilidad con filtros simples existentes
        $this->applySimpleFilters($query, $request);

        // Ordenamiento
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Aplicar filtros simples para mantener compatibilidad
     */
    protected function applySimpleFilters($query, $request): void
    {
        // Solo aplicar si no hay filtros avanzados
        if (!$request->filled('advanced_filters')) {
            // Filtro por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtro por categoría
            if ($request->filled('categoria_id')) {
                $query->where('categoria_id', $request->categoria_id);
            }
        }
    }

    /**
     * Obtener configuración de campos para filtros avanzados
     */
    public function getFilterFieldsConfig(): array
    {
        // Cargar categorías para el select
        $categorias = Categoria::activas()->get()->map(fn($c) => [
            'value' => $c->id,
            'label' => $c->nombre
        ]);

        return [
            [
                'name' => 'titulo',
                'label' => 'Título',
                'type' => 'text',
            ],
            [
                'name' => 'descripcion',
                'label' => 'Descripción',
                'type' => 'text',
            ],
            [
                'name' => 'categoria_id',
                'label' => 'Categoría',
                'type' => 'select',
                'options' => $categorias->toArray(),
            ],
            [
                'name' => 'estado',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'borrador', 'label' => 'Borrador'],
                    ['value' => 'activa', 'label' => 'Activa'],
                    ['value' => 'finalizada', 'label' => 'Finalizada'],
                ],
            ],
            [
                'name' => 'fecha_inicio',
                'label' => 'Fecha de Inicio',
                'type' => 'datetime',
            ],
            [
                'name' => 'fecha_fin',
                'label' => 'Fecha de Fin',
                'type' => 'datetime',
            ],
            [
                'name' => 'limite_censo',
                'label' => 'Límite del Censo',
                'type' => 'datetime',
            ],
            [
                'name' => 'resultados_publicos',
                'label' => 'Resultados Públicos',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Sí'],
                    ['value' => 0, 'label' => 'No'],
                ],
            ],
            [
                'name' => 'votantes_count',
                'label' => 'Cantidad de Votantes',
                'type' => 'number',
                'operators' => ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal', 'between'],
            ],
            [
                'name' => 'created_at',
                'label' => 'Fecha de Creación',
                'type' => 'datetime',
            ],
        ];
    }

    /**
     * Obtener votantes asignados con paginación y búsqueda
     */
    public function getAssignedVoters(Votacion $votacion, string $query = '', int $page = 1, int $perPage = 50): LengthAwarePaginator
    {
        // Construir consulta base de votantes asignados
        $votantesQuery = $votacion->votantes()
            ->select('users.id', 'users.name', 'users.email', 'users.documento_identidad', 'users.telefono');

        // Aplicar búsqueda si hay query
        if (strlen($query) >= 2) {
            $votantesQuery->where(function ($q) use ($query) {
                $q->where('users.name', 'like', "%{$query}%")
                    ->orWhere('users.email', 'like', "%{$query}%")
                    ->orWhere('users.documento_identidad', 'like', "%{$query}%")
                    ->orWhere('users.telefono', 'like', "%{$query}%");
            });
        }

        // Ordenar por nombre
        $votantesQuery->orderBy('users.name');

        // Aplicar paginación
        return $votantesQuery->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Buscar usuarios disponibles para asignar
     */
    public function searchAvailableUsers(Votacion $votacion, string $search = '', int $perPage = 50): LengthAwarePaginator
    {
        // Obtener IDs de votantes ya asignados
        $votantesAsignadosIds = $votacion->votantes()->pluck('users.id');

        // Construir consulta base
        $usersQuery = User::where('activo', true)
            ->whereNotIn('id', $votantesAsignadosIds);

        // Aplicar búsqueda si existe
        if (!empty($search)) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('documento_identidad', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        // Paginar resultados
        return $usersQuery
            ->select('id', 'name', 'email', 'documento_identidad', 'telefono')
            ->orderBy('name')
            ->paginate($perPage);
    }
}
