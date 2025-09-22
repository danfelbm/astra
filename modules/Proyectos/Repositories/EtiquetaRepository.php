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

    /**
     * Obtiene el árbol jerárquico de etiquetas.
     */
    public function getArbol(?int $categoriaId = null): Collection
    {
        $query = Etiqueta::raices()
            ->with(['children' => function ($q) {
                $q->with('children.children.children.children'); // Hasta 5 niveles
            }, 'categoria']);

        if ($categoriaId) {
            $query->where('categoria_etiqueta_id', $categoriaId);
        }

        return $query->orderBy('nombre')->get();
    }

    /**
     * Obtiene las etiquetas hijas de una etiqueta.
     */
    public function getHijos(int $etiquetaId): Collection
    {
        return Etiqueta::where('parent_id', $etiquetaId)
            ->with(['categoria', 'children'])
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtiene las etiquetas raíz.
     */
    public function getRaices(?int $categoriaId = null): Collection
    {
        $query = Etiqueta::raices()->with(['categoria', 'children']);

        if ($categoriaId) {
            $query->where('categoria_etiqueta_id', $categoriaId);
        }

        return $query->orderBy('nombre')->get();
    }

    /**
     * Busca una etiqueta por su ruta jerárquica.
     */
    public function buscarPorRuta(string $ruta): ?Etiqueta
    {
        return Etiqueta::where('ruta', $ruta)
            ->where('tenant_id', auth()->user()->tenant_id ?? null)
            ->first();
    }

    /**
     * Obtiene etiquetas con jerarquía completa para un dropdown.
     */
    public function getParaSelector(?int $excluirId = null): Collection
    {
        $query = Etiqueta::query()
            ->with(['parent', 'categoria'])
            ->orderBy('ruta');

        if ($excluirId) {
            // Excluir la etiqueta y sus descendientes
            $etiqueta = Etiqueta::find($excluirId);
            if ($etiqueta) {
                $descendientesIds = $etiqueta->getDescendientes()->pluck('id')->toArray();
                $descendientesIds[] = $excluirId;
                $query->whereNotIn('id', $descendientesIds);
            }
        }

        return $query->get()->map(function ($etiqueta) {
            // Agregar indentación visual basada en el nivel
            $prefijo = str_repeat('— ', $etiqueta->nivel);
            $etiqueta->nombre_jerarquico = $prefijo . $etiqueta->nombre;
            return $etiqueta;
        });
    }

    /**
     * Busca etiquetas incluyendo búsqueda en rutas.
     */
    public function search(string $termino, ?int $categoriaId = null): Collection
    {
        $query = Etiqueta::query()
            ->where(function ($q) use ($termino) {
                $q->where('nombre', 'like', "%{$termino}%")
                  ->orWhere('slug', 'like', "%{$termino}%")
                  ->orWhere('ruta', 'like', "%{$termino}%")
                  ->orWhere('descripcion', 'like', "%{$termino}%");
            })
            ->with(['categoria', 'parent']);

        if ($categoriaId) {
            $query->where('categoria_etiqueta_id', $categoriaId);
        }

        return $query->orderBy('ruta')->limit(20)->get();
    }

    /**
     * Obtiene el camino jerárquico completo de una etiqueta.
     */
    public function getCamino(int $etiquetaId): Collection
    {
        $etiqueta = Etiqueta::find($etiquetaId);
        if (!$etiqueta) {
            return new Collection();
        }

        $camino = collect([$etiqueta]);
        $actual = $etiqueta;

        while ($actual->parent) {
            $camino->prepend($actual->parent);
            $actual = $actual->parent;
        }

        return $camino;
    }

    /**
     * Obtiene estadísticas de jerarquía.
     */
    public function getEstadisticasJerarquia(): array
    {
        $totalRaices = Etiqueta::raices()->count();
        $totalConHijos = Etiqueta::has('children')->count();
        $nivelMaximo = Etiqueta::max('nivel') ?? 0;

        // Contar etiquetas por nivel
        $etiquetasPorNivel = [];
        for ($i = 0; $i <= $nivelMaximo; $i++) {
            $etiquetasPorNivel[$i] = Etiqueta::where('nivel', $i)->count();
        }

        return [
            'total_raices' => $totalRaices,
            'total_con_hijos' => $totalConHijos,
            'nivel_maximo' => $nivelMaximo,
            'etiquetas_por_nivel' => $etiquetasPorNivel,
            'promedio_hijos' => $totalConHijos > 0
                ? Etiqueta::has('children')->withCount('children')->avg('children_count')
                : 0,
        ];
    }
}