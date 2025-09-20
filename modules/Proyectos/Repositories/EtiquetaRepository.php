<?php

namespace Modules\Proyectos\Repositories;

use Modules\Proyectos\Models\Etiqueta;
use Modules\Proyectos\Models\Proyecto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class EtiquetaRepository
{
    /**
     * Obtiene todas las etiquetas paginadas.
     */
    public function getAllPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Etiqueta::query()
            ->with('categoria');

        // Búsqueda
        if ($request->filled('search')) {
            $query->buscar($request->search);
        }

        // Filtro por categoría
        if ($request->filled('categoria_id')) {
            $query->porCategoria($request->categoria_id);
        }

        // Filtro por uso
        if ($request->filled('sin_uso') && $request->sin_uso) {
            $query->noUtilizadas();
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'nombre');
        $sortOrder = $request->get('sort_order', 'asc');

        switch ($sortBy) {
            case 'usos':
                $query->orderBy('usos_count', $sortOrder);
                break;
            case 'categoria':
                $query->join('categorias_etiquetas', 'etiquetas.categoria_etiqueta_id', '=', 'categorias_etiquetas.id')
                    ->orderBy('categorias_etiquetas.nombre', $sortOrder)
                    ->select('etiquetas.*');
                break;
            case 'nombre':
            default:
                $query->orderBy('nombre', $sortOrder);
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * Busca una etiqueta por ID.
     */
    public function find(int $id): ?Etiqueta
    {
        return Etiqueta::find($id);
    }

    /**
     * Busca una etiqueta por ID con relaciones.
     */
    public function findWithRelations(int $id, array $relations = []): ?Etiqueta
    {
        return Etiqueta::with($relations)->find($id);
    }

    /**
     * Busca una etiqueta por slug.
     */
    public function findBySlug(string $slug): ?Etiqueta
    {
        return Etiqueta::where('slug', $slug)
            ->where('tenant_id', auth()->user()->tenant_id ?? null)
            ->first();
    }

    /**
     * Crea una nueva etiqueta.
     */
    public function create(array $data): Etiqueta
    {
        return Etiqueta::create($data);
    }

    /**
     * Actualiza una etiqueta.
     */
    public function update(Etiqueta $etiqueta, array $data): bool
    {
        return $etiqueta->update($data);
    }

    /**
     * Elimina una etiqueta.
     */
    public function delete(Etiqueta $etiqueta): bool
    {
        return $etiqueta->delete();
    }

    /**
     * Obtiene etiquetas por IDs.
     */
    public function getByIds(array $ids): Collection
    {
        return Etiqueta::whereIn('id', $ids)->get();
    }

    /**
     * Obtiene las etiquetas más usadas.
     */
    public function getMostUsed(int $limit = 10): Collection
    {
        return Etiqueta::masUsadas($limit)->get();
    }

    /**
     * Obtiene etiquetas para un proyecto específico.
     */
    public function getForProyecto(int $proyectoId): Collection
    {
        $proyecto = Proyecto::find($proyectoId);

        if (!$proyecto) {
            return new Collection();
        }

        return $proyecto->etiquetas()
            ->with('categoria')
            ->orderBy('proyecto_etiqueta.orden')
            ->get();
    }

    /**
     * Obtiene etiquetas sugeridas basadas en texto.
     */
    public function getSuggestionsByText(string $texto, int $limit = 10): Collection
    {
        // Analizar el texto para extraer posibles etiquetas
        // Buscar coincidencias con etiquetas existentes
        $palabras = str_word_count(strtolower($texto), 1);

        return Etiqueta::query()
            ->where(function ($query) use ($palabras) {
                foreach ($palabras as $palabra) {
                    if (strlen($palabra) > 3) { // Solo palabras de más de 3 caracteres
                        $query->orWhere('nombre', 'like', "%{$palabra}%");
                    }
                }
            })
            ->masUsadas($limit)
            ->get();
    }

    /**
     * Obtiene estadísticas de etiquetas.
     */
    public function getEstadisticas(): array
    {
        $total = Etiqueta::count();
        $usadas = Etiqueta::where('usos_count', '>', 0)->count();
        $noUsadas = Etiqueta::noUtilizadas()->count();
        $masUsada = Etiqueta::masUsadas(1)->first();

        return [
            'total' => $total,
            'usadas' => $usadas,
            'no_usadas' => $noUsadas,
            'porcentaje_uso' => $total > 0 ? round(($usadas / $total) * 100, 2) : 0,
            'mas_usada' => $masUsada,
            'promedio_usos' => Etiqueta::avg('usos_count') ?? 0,
        ];
    }

    /**
     * Busca etiquetas con autocomplete.
     */
    public function autocomplete(string $termino, int $limit = 10): Collection
    {
        return Etiqueta::query()
            ->where('nombre', 'like', "{$termino}%")
            ->orWhere('slug', 'like', "{$termino}%")
            ->with('categoria')
            ->masUsadas($limit)
            ->get();
    }

    /**
     * Obtiene etiquetas agrupadas por categoría.
     */
    public function getGroupedByCategoria(): Collection
    {
        return Etiqueta::query()
            ->with('categoria')
            ->get()
            ->groupBy('categoria.nombre');
    }
}