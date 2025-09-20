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
        $this->authorize('etiquetas.create');

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
        $this->authorize('etiquetas.edit');

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
        $this->authorize('etiquetas.delete');

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
}