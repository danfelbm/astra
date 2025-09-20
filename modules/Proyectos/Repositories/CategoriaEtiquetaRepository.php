<?php

namespace Modules\Proyectos\Repositories;

use Modules\Proyectos\Models\CategoriaEtiqueta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class CategoriaEtiquetaRepository
{
    /**
     * Obtiene todas las categorías paginadas.
     */
    public function getAllPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = CategoriaEtiqueta::query()
            ->withCount('etiquetas')
            ->with(['etiquetas' => function ($q) {
                $q->orderBy('nombre');
            }]);

        // Búsqueda
        if ($request->filled('search')) {
            $query->buscar($request->search);
        }

        // Filtro por estado
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'orden');
        $sortOrder = $request->get('sort_order', 'asc');

        switch ($sortBy) {
            case 'nombre':
                $query->orderBy('nombre', $sortOrder);
                break;
            case 'etiquetas':
                $query->orderBy('etiquetas_count', $sortOrder);
                break;
            case 'orden':
            default:
                $query->ordenado();
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtiene todas las categorías sin paginación.
     */
    public function getAll(): Collection
    {
        return CategoriaEtiqueta::ordenado()->get();
    }

    /**
     * Obtiene todas las categorías activas.
     */
    public function getActivas(): Collection
    {
        return CategoriaEtiqueta::activas()->ordenado()->get();
    }

    /**
     * Busca una categoría por ID.
     */
    public function find(int $id): ?CategoriaEtiqueta
    {
        return CategoriaEtiqueta::find($id);
    }

    /**
     * Busca una categoría por ID con relaciones.
     */
    public function findWithRelations(int $id, array $relations = []): ?CategoriaEtiqueta
    {
        return CategoriaEtiqueta::with($relations)->find($id);
    }

    /**
     * Busca una categoría por slug.
     */
    public function findBySlug(string $slug): ?CategoriaEtiqueta
    {
        return CategoriaEtiqueta::where('slug', $slug)
            ->where('tenant_id', auth()->user()->tenant_id ?? null)
            ->first();
    }

    /**
     * Crea una nueva categoría.
     */
    public function create(array $data): CategoriaEtiqueta
    {
        return CategoriaEtiqueta::create($data);
    }

    /**
     * Actualiza una categoría.
     */
    public function update(CategoriaEtiqueta $categoria, array $data): bool
    {
        return $categoria->update($data);
    }

    /**
     * Elimina una categoría.
     */
    public function delete(CategoriaEtiqueta $categoria): bool
    {
        return $categoria->delete();
    }

    /**
     * Obtiene categorías con etiquetas.
     */
    public function getWithEtiquetas(): Collection
    {
        return CategoriaEtiqueta::with('etiquetas')
            ->ordenado()
            ->get();
    }

    /**
     * Obtiene categorías activas con etiquetas.
     */
    public function getActivasWithEtiquetas(): Collection
    {
        return CategoriaEtiqueta::activas()
            ->with(['etiquetas' => function ($query) {
                $query->orderBy('nombre');
            }])
            ->ordenado()
            ->get();
    }

    /**
     * Obtiene el próximo orden disponible.
     */
    public function getNextOrden(): int
    {
        return CategoriaEtiqueta::max('orden') + 1;
    }

    /**
     * Verifica si una categoría tiene etiquetas.
     */
    public function hasEtiquetas(CategoriaEtiqueta $categoria): bool
    {
        return $categoria->etiquetas()->exists();
    }

    /**
     * Obtiene el conteo de etiquetas por categoría.
     */
    public function getEtiquetasCount(CategoriaEtiqueta $categoria): int
    {
        return $categoria->etiquetas()->count();
    }

    /**
     * Busca categorías con autocomplete.
     */
    public function autocomplete(string $termino, int $limit = 10): Collection
    {
        return CategoriaEtiqueta::query()
            ->where('nombre', 'like', "{$termino}%")
            ->orWhere('slug', 'like', "{$termino}%")
            ->activas()
            ->ordenado()
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene estadísticas de categorías.
     */
    public function getEstadisticas(): array
    {
        $categorias = CategoriaEtiqueta::withCount('etiquetas')->get();

        return [
            'total' => $categorias->count(),
            'activas' => $categorias->where('activo', true)->count(),
            'inactivas' => $categorias->where('activo', false)->count(),
            'total_etiquetas' => $categorias->sum('etiquetas_count'),
            'promedio_etiquetas' => $categorias->avg('etiquetas_count') ?? 0,
            'categoria_mayor' => $categorias->sortByDesc('etiquetas_count')->first(),
            'categorias_vacias' => $categorias->where('etiquetas_count', 0)->count(),
        ];
    }

    /**
     * Reordena las categorías.
     */
    public function reorder(array $ordenCategorias): void
    {
        foreach ($ordenCategorias as $item) {
            CategoriaEtiqueta::where('id', $item['id'])
                ->update(['orden' => $item['orden']]);
        }
    }
}