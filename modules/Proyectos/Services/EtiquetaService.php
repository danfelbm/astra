<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\Etiqueta;
use Modules\Proyectos\Models\CategoriaEtiqueta;
use Modules\Proyectos\Models\Proyecto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EtiquetaService
{
    /**
     * Crea una nueva etiqueta.
     */
    public function create(array $data): Etiqueta
    {
        return DB::transaction(function () use ($data) {
            // Validar jerarquía si se proporciona parent_id
            if (!empty($data['parent_id'])) {
                $this->validarJerarquia(null, $data['parent_id']);
            }

            $etiqueta = Etiqueta::create([
                'nombre' => $data['nombre'],
                'slug' => Etiqueta::generarSlug($data['nombre']),
                'categoria_etiqueta_id' => $data['categoria_etiqueta_id'],
                'parent_id' => $data['parent_id'] ?? null,
                'color' => $data['color'] ?? null,
                'descripcion' => $data['descripcion'] ?? null,
            ]);

            activity()
                ->performedOn($etiqueta)
                ->log("Etiqueta '{$etiqueta->nombre}' creada");

            $this->limpiarCache();

            return $etiqueta;
        });
    }

    /**
     * Actualiza una etiqueta existente.
     */
    public function update(Etiqueta $etiqueta, array $data): Etiqueta
    {
        return DB::transaction(function () use ($etiqueta, $data) {
            $cambios = [];

            if (isset($data['nombre']) && $data['nombre'] !== $etiqueta->nombre) {
                $cambios[] = "nombre de '{$etiqueta->nombre}' a '{$data['nombre']}'";
            }

            // Validar jerarquía si cambia el parent_id
            if (isset($data['parent_id']) && $data['parent_id'] !== $etiqueta->parent_id) {
                $this->validarJerarquia($etiqueta->id, $data['parent_id']);
                $cambios[] = "padre cambiado";
            }

            $etiqueta->update($data);

            if (!empty($cambios)) {
                activity()
                    ->performedOn($etiqueta)
                    ->withProperties(['cambios' => $cambios])
                    ->log('Etiqueta actualizada');
            }

            $this->limpiarCache();

            return $etiqueta->fresh();
        });
    }

    /**
     * Elimina una etiqueta.
     */
    public function delete(Etiqueta $etiqueta): bool
    {
        return DB::transaction(function () use ($etiqueta) {
            // Verificar si tiene proyectos asociados
            if ($etiqueta->proyectos()->exists()) {
                throw new \Exception("No se puede eliminar la etiqueta porque está siendo usada en proyectos.");
            }

            // Si tiene hijos, promoverlos al nivel del padre actual
            if ($etiqueta->children()->exists()) {
                $etiqueta->children()->update([
                    'parent_id' => $etiqueta->parent_id
                ]);

                // Recalcular niveles y rutas de los hijos
                foreach ($etiqueta->children as $hijo) {
                    $hijo->recalcularNivel();
                    $hijo->recalcularRuta();
                }
            }

            $nombre = $etiqueta->nombre;
            $resultado = $etiqueta->delete();

            activity()
                ->log("Etiqueta '{$nombre}' eliminada");

            $this->limpiarCache();

            return $resultado;
        });
    }

    /**
     * Crea una etiqueta desde un proyecto (con auto-categorización si es necesario).
     */
    public function createFromProject(string $nombre, int $proyectoId, int $categoriaId = null): Etiqueta
    {
        return DB::transaction(function () use ($nombre, $proyectoId, $categoriaId) {
            // Si no se proporciona categoría, usar o crear la categoría "General"
            if (!$categoriaId) {
                $categoriaGeneral = CategoriaEtiqueta::firstOrCreate(
                    [
                        'slug' => 'general',
                        'tenant_id' => auth()->user()->tenant_id ?? null,
                    ],
                    [
                        'nombre' => 'General',
                        'color' => 'gray',
                        'orden' => 999,
                    ]
                );
                $categoriaId = $categoriaGeneral->id;
            }

            // Buscar o crear la etiqueta
            $etiqueta = Etiqueta::buscarOCrear($nombre, $categoriaId);

            // Asociar al proyecto
            $proyecto = Proyecto::find($proyectoId);
            if ($proyecto) {
                $proyecto->agregarEtiqueta($etiqueta->id);
            }

            $this->limpiarCache();

            return $etiqueta;
        });
    }

    /**
     * Busca o crea una etiqueta por nombre.
     */
    public function findOrCreate(string $nombre, int $categoriaId): Etiqueta
    {
        $etiqueta = Etiqueta::buscarOCrear($nombre, $categoriaId);
        $this->limpiarCache();
        return $etiqueta;
    }

    /**
     * Obtiene sugerencias de etiquetas para un proyecto.
     */
    public function getSuggestions(Proyecto $proyecto, int $limite = 10): Collection
    {
        $cacheKey = "etiquetas_sugerencias_{$proyecto->id}_{$limite}";

        return Cache::remember($cacheKey, 300, function () use ($proyecto, $limite) {
            $sugerencias = collect();

            // 1. Etiquetas frecuentes en proyectos del mismo responsable
            if ($proyecto->responsable_id) {
                $etiquetasResponsable = Etiqueta::query()
                    ->whereHas('proyectos', function ($q) use ($proyecto) {
                        $q->where('responsable_id', $proyecto->responsable_id)
                          ->where('id', '!=', $proyecto->id);
                    })
                    ->masUsadas($limite / 2)
                    ->get();

                $sugerencias = $sugerencias->merge($etiquetasResponsable);
            }

            // 2. Etiquetas frecuentes en proyectos del mismo estado
            $etiquetasEstado = Etiqueta::query()
                ->whereHas('proyectos', function ($q) use ($proyecto) {
                    $q->where('estado', $proyecto->estado)
                      ->where('id', '!=', $proyecto->id);
                })
                ->masUsadas($limite / 2)
                ->get();

            $sugerencias = $sugerencias->merge($etiquetasEstado);

            // 3. Etiquetas más usadas globalmente
            $etiquetasGlobales = Etiqueta::masUsadas($limite / 4)->get();
            $sugerencias = $sugerencias->merge($etiquetasGlobales);

            // Eliminar duplicados y etiquetas ya asignadas
            $etiquetasActuales = $proyecto->etiquetas->pluck('id');

            return $sugerencias
                ->unique('id')
                ->whereNotIn('id', $etiquetasActuales)
                ->take($limite)
                ->values();
        });
    }

    /**
     * Fusiona dos o más etiquetas en una.
     */
    public function mergeEtiquetas(array $etiquetaIds, int $etiquetaDestinoId): Etiqueta
    {
        return DB::transaction(function () use ($etiquetaIds, $etiquetaDestinoId) {
            $etiquetaDestino = Etiqueta::findOrFail($etiquetaDestinoId);

            // Quitar la etiqueta destino del array de IDs a fusionar
            $etiquetasAFusionar = array_diff($etiquetaIds, [$etiquetaDestinoId]);

            foreach ($etiquetasAFusionar as $etiquetaId) {
                $etiquetaOrigen = Etiqueta::find($etiquetaId);
                if (!$etiquetaOrigen) continue;

                // Transferir todos los proyectos a la etiqueta destino
                foreach ($etiquetaOrigen->proyectos as $proyecto) {
                    // Si el proyecto no tiene ya la etiqueta destino, agregarla
                    if (!$proyecto->etiquetas()->where('etiqueta_id', $etiquetaDestinoId)->exists()) {
                        $proyecto->agregarEtiqueta($etiquetaDestinoId);
                    }
                    // Quitar la etiqueta origen
                    $proyecto->quitarEtiqueta($etiquetaId);
                }

                // Eliminar la etiqueta origen
                $etiquetaOrigen->delete();
            }

            // Recalcular usos de la etiqueta destino
            $etiquetaDestino->recalcularUsos();

            activity()
                ->performedOn($etiquetaDestino)
                ->withProperties(['etiquetas_fusionadas' => $etiquetasAFusionar])
                ->log('Etiquetas fusionadas');

            $this->limpiarCache();

            return $etiquetaDestino->fresh();
        });
    }

    /**
     * Actualiza el contador de usos de una etiqueta.
     */
    public function updateUsageCount(Etiqueta $etiqueta): void
    {
        $etiqueta->recalcularUsos();
        $this->limpiarCache();
    }

    /**
     * Obtiene etiquetas por categoría.
     */
    public function getByCategoria(int $categoriaId): Collection
    {
        return Etiqueta::porCategoria($categoriaId)
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtiene etiquetas no utilizadas.
     */
    public function getUnused(): Collection
    {
        return Etiqueta::noUtilizadas()
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Elimina etiquetas no utilizadas.
     */
    public function deleteUnused(): int
    {
        $etiquetasEliminadas = 0;

        DB::transaction(function () use (&$etiquetasEliminadas) {
            $etiquetasNoUsadas = $this->getUnused();

            foreach ($etiquetasNoUsadas as $etiqueta) {
                $etiqueta->delete();
                $etiquetasEliminadas++;
            }

            if ($etiquetasEliminadas > 0) {
                activity()
                    ->log("Se eliminaron {$etiquetasEliminadas} etiquetas no utilizadas");
            }
        });

        $this->limpiarCache();

        return $etiquetasEliminadas;
    }

    /**
     * Busca etiquetas por término.
     */
    public function search(string $termino, int $limite = 20): Collection
    {
        return Etiqueta::buscar($termino)
            ->with('categoria')
            ->limit($limite)
            ->get();
    }

    /**
     * Establece el padre de una etiqueta.
     */
    public function establecerPadre(Etiqueta $etiqueta, ?int $padreId): Etiqueta
    {
        return DB::transaction(function () use ($etiqueta, $padreId) {
            // Validar que no se cree un ciclo
            if ($padreId) {
                $this->validarJerarquia($etiqueta->id, $padreId);
            }

            $etiqueta->parent_id = $padreId;
            $etiqueta->save();

            // Recalcular nivel y ruta
            $etiqueta->recalcularNivel();
            $etiqueta->recalcularRuta();

            // Recalcular descendientes
            foreach ($etiqueta->getDescendientes() as $descendiente) {
                $descendiente->recalcularNivel();
                $descendiente->recalcularRuta();
            }

            activity()
                ->performedOn($etiqueta)
                ->withProperties(['nuevo_padre_id' => $padreId])
                ->log('Jerarquía de etiqueta actualizada');

            $this->limpiarCache();

            return $etiqueta->fresh();
        });
    }

    /**
     * Mueve una etiqueta en la jerarquía.
     */
    public function moverEnJerarquia(Etiqueta $etiqueta, ?int $nuevoPadreId): Etiqueta
    {
        return $this->establecerPadre($etiqueta, $nuevoPadreId);
    }

    /**
     * Obtiene el árbol jerárquico de etiquetas.
     */
    public function obtenerArbolJerarquico(?int $categoriaId = null): Collection
    {
        $query = Etiqueta::raices()->with('descendants', 'categoria');

        if ($categoriaId) {
            $query->where('categoria_etiqueta_id', $categoriaId);
        }

        return $query->orderBy('nombre')->get();
    }

    /**
     * Valida que no se creen ciclos en la jerarquía.
     */
    public function validarJerarquia(?int $etiquetaId, ?int $padreId): bool
    {
        if (!$padreId) return true;

        // No puede ser su propio padre
        if ($etiquetaId && $etiquetaId == $padreId) {
            throw new \Exception('Una etiqueta no puede ser su propio padre');
        }

        // El padre debe existir
        $padre = Etiqueta::find($padreId);
        if (!$padre) {
            throw new \Exception('La etiqueta padre no existe');
        }

        // Si estamos actualizando, verificar que el padre no sea descendiente
        if ($etiquetaId) {
            $etiqueta = Etiqueta::find($etiquetaId);
            if ($etiqueta && !$etiqueta->puedeSerHijoDe($padreId)) {
                throw new \Exception('No se puede crear un ciclo en la jerarquía');
            }
        }

        // Verificar límite de profundidad (máximo 5 niveles)
        $nivel = 0;
        $padreActual = $padre;
        while ($padreActual && $nivel < 5) {
            $nivel++;
            $padreActual = $padreActual->parent;
        }

        if ($nivel >= 5) {
            throw new \Exception('Se ha alcanzado el límite máximo de profundidad en la jerarquía (5 niveles)');
        }

        return true;
    }

    /**
     * Recalcula niveles y rutas de toda la jerarquía.
     */
    public function recalcularJerarquia(): void
    {
        DB::transaction(function () {
            // Obtener todas las raíces
            $raices = Etiqueta::raices()->get();

            foreach ($raices as $raiz) {
                $this->recalcularNivelesRecursivo($raiz, 0, '');
            }

            $this->limpiarCache();
        });
    }

    /**
     * Recalcula niveles y rutas recursivamente.
     */
    private function recalcularNivelesRecursivo(Etiqueta $etiqueta, int $nivel, string $rutaPadre): void
    {
        $etiqueta->nivel = $nivel;
        $etiqueta->ruta = $rutaPadre ? $rutaPadre . '/' . $etiqueta->slug : $etiqueta->slug;
        $etiqueta->save();

        foreach ($etiqueta->children as $hijo) {
            $this->recalcularNivelesRecursivo($hijo, $nivel + 1, $etiqueta->ruta);
        }
    }

    /**
     * Limpia la caché relacionada con etiquetas.
     */
    protected function limpiarCache(): void
    {
        // Limpiar cache específico sin usar tags para compatibilidad
        Cache::forget('etiquetas_*');
        Cache::forget('categorias_etiquetas_*');

        // Si se configura Redis en el futuro, descomentar:
        // Cache::tags(['etiquetas'])->flush();
    }
}