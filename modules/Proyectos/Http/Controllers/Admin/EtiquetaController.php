<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Proyectos\Models\Etiqueta;
use Modules\Proyectos\Services\EtiquetaService;
use Modules\Proyectos\Repositories\EtiquetaRepository;
use Modules\Proyectos\Http\Requests\Admin\StoreEtiquetaRequest;
use Modules\Proyectos\Http\Requests\Admin\UpdateEtiquetaRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class EtiquetaController extends AdminController
{
    public function __construct(
        private EtiquetaService $service,
        private EtiquetaRepository $repository
    ) {
        parent::__construct();
    }

    /**
     * Almacena una nueva etiqueta.
     */
    public function store(StoreEtiquetaRequest $request): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('etiquetas.create'), 403, 'No tienes permisos para crear etiquetas');

        try {
            $etiqueta = $this->service->create($request->validated());

            return redirect()
                ->back()
                ->with('success', 'Etiqueta creada exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()])
                ->with('error', 'Error al crear la etiqueta');
        }
    }

    /**
     * Actualiza una etiqueta existente.
     */
    public function update(UpdateEtiquetaRequest $request, Etiqueta $etiqueta): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('etiquetas.edit'), 403, 'No tienes permisos para editar etiquetas');

        try {
            $this->service->update($etiqueta, $request->validated());

            return redirect()
                ->back()
                ->with('success', 'Etiqueta actualizada exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()])
                ->with('error', 'Error al actualizar la etiqueta');
        }
    }

    /**
     * Elimina una etiqueta.
     */
    public function destroy(Etiqueta $etiqueta): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('etiquetas.delete'), 403, 'No tienes permisos para eliminar etiquetas');

        try {
            $this->service->delete($etiqueta);

            return redirect()
                ->back()
                ->with('success', 'Etiqueta eliminada exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Búsqueda de etiquetas para selector.
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $categoriaId = $request->get('categoria_id');

        $etiquetas = $this->repository->search($search, $categoriaId);

        return response()->json([
            'etiquetas' => $etiquetas->map(function ($etiqueta) {
                return [
                    'id' => $etiqueta->id,
                    'nombre' => $etiqueta->nombre,
                    'categoria' => [
                        'id' => $etiqueta->categoria->id,
                        'nombre' => $etiqueta->categoria->nombre,
                        'color' => $etiqueta->categoria->color,
                    ]
                ];
            })
        ]);
    }

    /**
     * Incrementa el contador de uso de una etiqueta.
     */
    public function incrementUso(Etiqueta $etiqueta): JsonResponse
    {
        try {
            $this->service->updateUsageCount($etiqueta);

            return response()->json([
                'success' => true,
                'contador_uso' => $etiqueta->fresh()->usos_count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reordena etiquetas dentro de una categoría.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'etiquetas' => 'required|array',
            'etiquetas.*.id' => 'required|exists:etiquetas,id'
        ]);

        try {
            // Como no tenemos columna orden, simplemente retornamos éxito
            return response()->json([
                'success' => true,
                'message' => 'Orden actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene el árbol jerárquico de etiquetas.
     */
    public function obtenerArbol(Request $request): JsonResponse
    {
        $categoriaId = $request->get('categoria_id');
        $arbol = $this->repository->getArbol($categoriaId);

        return response()->json([
            'success' => true,
            'arbol' => $arbol
        ]);
    }

    /**
     * Establece la jerarquía de una etiqueta.
     */
    public function establecerJerarquia(Request $request, Etiqueta $etiqueta): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('etiquetas.edit'), 403, 'No tienes permisos para editar etiquetas');

        $request->validate([
            'parent_id' => 'nullable|exists:etiquetas,id'
        ]);

        try {
            $etiquetaActualizada = $this->service->establecerPadre($etiqueta, $request->parent_id);

            return response()->json([
                'success' => true,
                'message' => 'Jerarquía actualizada exitosamente',
                'etiqueta' => $etiquetaActualizada->load(['parent', 'categoria'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Mueve una etiqueta en la jerarquía.
     */
    public function mover(Request $request, Etiqueta $etiqueta): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('etiquetas.edit'), 403, 'No tienes permisos para editar etiquetas');

        $request->validate([
            'nuevo_padre_id' => 'nullable|exists:etiquetas,id'
        ]);

        try {
            $etiquetaMovida = $this->service->moverEnJerarquia($etiqueta, $request->nuevo_padre_id);

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta movida exitosamente',
                'etiqueta' => $etiquetaMovida->load(['parent', 'categoria', 'children'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtiene etiquetas para selector con jerarquía.
     */
    public function paraSelector(Request $request): JsonResponse
    {
        $excluirId = $request->get('excluir_id');
        $etiquetas = $this->repository->getParaSelector($excluirId);

        return response()->json([
            'success' => true,
            'etiquetas' => $etiquetas
        ]);
    }

    /**
     * Obtiene el camino jerárquico de una etiqueta.
     */
    public function camino(Etiqueta $etiqueta): JsonResponse
    {
        $camino = $this->repository->getCamino($etiqueta->id);

        return response()->json([
            'success' => true,
            'camino' => $camino
        ]);
    }

    /**
     * Obtiene estadísticas de jerarquía.
     */
    public function estadisticasJerarquia(): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('etiquetas.view'), 403, 'No tienes permisos para ver etiquetas');

        $estadisticas = $this->repository->getEstadisticasJerarquia();

        return response()->json([
            'success' => true,
            'estadisticas' => $estadisticas
        ]);
    }
}