<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\CategoriaEtiqueta;
use Modules\Proyectos\Models\Etiqueta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoriaEtiquetaService
{
    /**
     * Crea una nueva categoría de etiquetas.
     */
    public function create(array $data): CategoriaEtiqueta
    {
        return DB::transaction(function () use ($data) {
            // Generar slug si no se proporciona
            if (!isset($data['slug'])) {
                $data['slug'] = CategoriaEtiqueta::generarSlug($data['nombre']);
            }

            // Establecer orden si no se proporciona
            if (!isset($data['orden'])) {
                $maxOrden = CategoriaEtiqueta::max('orden') ?? 0;
                $data['orden'] = $maxOrden + 1;
            }

            $categoria = CategoriaEtiqueta::create($data);

            activity()
                ->performedOn($categoria)
                ->log("Categoría de etiquetas '{$categoria->nombre}' creada");

            return $categoria;
        });
    }

    /**
     * Actualiza una categoría de etiquetas.
     */
    public function update(CategoriaEtiqueta $categoria, array $data): CategoriaEtiqueta
    {
        return DB::transaction(function () use ($categoria, $data) {
            $cambios = [];

            if (isset($data['nombre']) && $data['nombre'] !== $categoria->nombre) {
                $cambios[] = "nombre de '{$categoria->nombre}' a '{$data['nombre']}'";
            }

            if (isset($data['color']) && $data['color'] !== $categoria->color) {
                $cambios[] = "color de '{$categoria->color}' a '{$data['color']}'";
            }

            $categoria->update($data);

            if (!empty($cambios)) {
                activity()
                    ->performedOn($categoria)
                    ->withProperties(['cambios' => $cambios])
                    ->log('Categoría de etiquetas actualizada');
            }

            return $categoria->fresh();
        });
    }

    /**
     * Elimina una categoría de etiquetas.
     */
    public function delete(CategoriaEtiqueta $categoria, int $categoriaDestinoId = null): bool
    {
        return DB::transaction(function () use ($categoria, $categoriaDestinoId) {
            // Si hay etiquetas en esta categoría
            if ($categoria->etiquetas()->exists()) {
                if ($categoriaDestinoId) {
                    // Reasignar etiquetas a otra categoría
                    $categoriaDestino = CategoriaEtiqueta::findOrFail($categoriaDestinoId);

                    $categoria->etiquetas()->update([
                        'categoria_etiqueta_id' => $categoriaDestinoId
                    ]);

                    activity()
                        ->performedOn($categoria)
                        ->withProperties(['etiquetas_movidas_a' => $categoriaDestino->nombre])
                        ->log("Categoría '{$categoria->nombre}' eliminada y etiquetas reasignadas");
                } else {
                    // No se puede eliminar si tiene etiquetas y no se especifica destino
                    throw new \Exception("No se puede eliminar la categoría porque contiene etiquetas. Especifique una categoría de destino o elimine las etiquetas primero.");
                }
            }

            $nombre = $categoria->nombre;
            $resultado = $categoria->delete();

            activity()
                ->log("Categoría de etiquetas '{$nombre}' eliminada");

            return $resultado;
        });
    }

    /**
     * Reordena las categorías.
     */
    public function reorder(array $ordenCategorias): void
    {
        DB::transaction(function () use ($ordenCategorias) {
            foreach ($ordenCategorias as $item) {
                CategoriaEtiqueta::where('id', $item['id'])
                    ->update(['orden' => $item['orden']]);
            }

            activity()
                ->log('Categorías de etiquetas reordenadas');
        });
    }

    /**
     * Cambia el estado activo/inactivo de una categoría.
     */
    public function toggleActive(CategoriaEtiqueta $categoria): CategoriaEtiqueta
    {
        $categoria->activo = !$categoria->activo;
        $categoria->save();

        $estado = $categoria->activo ? 'activada' : 'desactivada';

        activity()
            ->performedOn($categoria)
            ->log("Categoría de etiquetas '{$categoria->nombre}' {$estado}");

        return $categoria;
    }

    /**
     * Obtiene todas las categorías activas ordenadas.
     */
    public function getActivas(): Collection
    {
        return CategoriaEtiqueta::activas()
            ->ordenado()
            ->get();
    }

    /**
     * Obtiene categorías con sus etiquetas.
     */
    public function getConEtiquetas(): Collection
    {
        return CategoriaEtiqueta::with('etiquetas')
            ->ordenado()
            ->get();
    }

    /**
     * Obtiene categorías activas con sus etiquetas.
     */
    public function getActivasConEtiquetas(): Collection
    {
        return CategoriaEtiqueta::activas()
            ->with(['etiquetas' => function ($query) {
                $query->orderBy('nombre');
            }])
            ->ordenado()
            ->get();
    }

    /**
     * Busca categorías por término.
     */
    public function search(string $termino): Collection
    {
        return CategoriaEtiqueta::buscar($termino)
            ->ordenado()
            ->get();
    }

    /**
     * Crea o obtiene la categoría "General".
     */
    public function getOrCreateGeneral(): CategoriaEtiqueta
    {
        return CategoriaEtiqueta::firstOrCreate(
            [
                'slug' => 'general',
                'tenant_id' => auth()->user()->tenant_id ?? null,
            ],
            [
                'nombre' => 'General',
                'color' => 'gray',
                'descripcion' => 'Categoría general para etiquetas sin clasificar',
                'orden' => 999,
                'activo' => true,
            ]
        );
    }

    /**
     * Obtiene estadísticas de las categorías.
     */
    public function getEstadisticas(): array
    {
        $categorias = CategoriaEtiqueta::withCount('etiquetas')->get();

        return [
            'total_categorias' => $categorias->count(),
            'categorias_activas' => $categorias->where('activo', true)->count(),
            'categorias_inactivas' => $categorias->where('activo', false)->count(),
            'total_etiquetas' => $categorias->sum('etiquetas_count'),
            'promedio_etiquetas_por_categoria' => $categorias->avg('etiquetas_count') ?? 0,
            'categoria_mas_usada' => $categorias->sortByDesc('etiquetas_count')->first(),
        ];
    }

    /**
     * Fusiona dos categorías.
     */
    public function merge(CategoriaEtiqueta $origen, CategoriaEtiqueta $destino): CategoriaEtiqueta
    {
        return DB::transaction(function () use ($origen, $destino) {
            // Mover todas las etiquetas de origen a destino
            $origen->etiquetas()->update([
                'categoria_etiqueta_id' => $destino->id
            ]);

            // Eliminar la categoría origen
            $nombreOrigen = $origen->nombre;
            $origen->delete();

            activity()
                ->performedOn($destino)
                ->withProperties(['categoria_fusionada' => $nombreOrigen])
                ->log("Categoría '{$nombreOrigen}' fusionada con '{$destino->nombre}'");

            return $destino->fresh()->loadCount('etiquetas');
        });
    }

    /**
     * Obtiene los colores disponibles para las categorías.
     */
    public function getColoresDisponibles(): array
    {
        return [
            'gray' => 'Gris',
            'red' => 'Rojo',
            'orange' => 'Naranja',
            'amber' => 'Ámbar',
            'yellow' => 'Amarillo',
            'lime' => 'Lima',
            'green' => 'Verde',
            'emerald' => 'Esmeralda',
            'teal' => 'Turquesa',
            'cyan' => 'Cian',
            'sky' => 'Cielo',
            'blue' => 'Azul',
            'indigo' => 'Índigo',
            'violet' => 'Violeta',
            'purple' => 'Púrpura',
            'fuchsia' => 'Fucsia',
            'pink' => 'Rosa',
            'rose' => 'Rosa claro',
        ];
    }

    /**
     * Obtiene los iconos sugeridos para las categorías.
     */
    public function getIconosSugeridos(): array
    {
        return [
            'Tag' => 'Etiqueta',
            'Hash' => 'Hashtag',
            'Bookmark' => 'Marcador',
            'Flag' => 'Bandera',
            'Star' => 'Estrella',
            'Heart' => 'Corazón',
            'Zap' => 'Rayo',
            'Target' => 'Objetivo',
            'Award' => 'Premio',
            'TrendingUp' => 'Tendencia',
            'Folder' => 'Carpeta',
            'Package' => 'Paquete',
            'Box' => 'Caja',
            'Layers' => 'Capas',
            'Grid' => 'Cuadrícula',
        ];
    }
}