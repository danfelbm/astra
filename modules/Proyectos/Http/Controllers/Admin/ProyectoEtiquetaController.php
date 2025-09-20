<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\Etiqueta;
use Modules\Proyectos\Services\EtiquetaService;
use Modules\Proyectos\Repositories\EtiquetaRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProyectoEtiquetaController extends AdminController
{
    public function __construct(
        private EtiquetaService $etiquetaService,
        private EtiquetaRepository $etiquetaRepository
    ) {
        parent::__construct();
    }

    /**
     * Muestra la gestión de etiquetas de un proyecto.
     */
    public function index(Proyecto $proyecto): Response
    {
        $this->authorize('proyectos.manage_tags');

        $proyecto->load(['etiquetas.categoria']);
        $categoriasConEtiquetas = $this->etiquetaRepository->getGroupedByCategoria();
        $sugerencias = $this->etiquetaService->getSuggestions($proyecto);

        return Inertia::render('Modules/Proyectos/Admin/Proyectos/Etiquetas', [
            'proyecto' => $proyecto,
            'categoriasConEtiquetas' => $categoriasConEtiquetas,
            'sugerencias' => $sugerencias,
        ]);
    }

    /**
     * Agrega una etiqueta al proyecto.
     */
    public function store(Request $request, Proyecto $proyecto): JsonResponse
    {
        $this->authorize('proyectos.manage_tags');

        $request->validate([
            'etiqueta_id' => 'nullable|exists:etiquetas,id',
            'nombre' => 'required_without:etiqueta_id|string|max:100',
            'categoria_id' => 'required_with:nombre|exists:categorias_etiquetas,id',
        ]);

        try {
            if ($request->etiqueta_id) {
                // Agregar etiqueta existente
                $proyecto->agregarEtiqueta($request->etiqueta_id);
                $etiqueta = Etiqueta::find($request->etiqueta_id);
            } else {
                // Crear nueva etiqueta y agregarla
                $etiqueta = $this->etiquetaService->createFromProject(
                    $request->nombre,
                    $proyecto->id,
                    $request->categoria_id
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta agregada exitosamente',
                'etiqueta' => $etiqueta->load('categoria'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la etiqueta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Quita una etiqueta del proyecto.
     */
    public function destroy(Proyecto $proyecto, Etiqueta $etiqueta): JsonResponse
    {
        $this->authorize('proyectos.manage_tags');

        try {
            $proyecto->quitarEtiqueta($etiqueta->id);

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta eliminada del proyecto exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la etiqueta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sincroniza todas las etiquetas del proyecto.
     */
    public function sync(Request $request, Proyecto $proyecto): JsonResponse
    {
        $this->authorize('proyectos.manage_tags');

        $request->validate([
            'etiquetas' => 'required|array',
            'etiquetas.*' => 'exists:etiquetas,id',
        ]);

        try {
            $proyecto->sincronizarEtiquetas($request->etiquetas);

            return response()->json([
                'success' => true,
                'message' => 'Etiquetas sincronizadas exitosamente',
                'etiquetas' => $proyecto->etiquetas()->with('categoria')->get(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar etiquetas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene sugerencias de etiquetas para un proyecto.
     */
    public function suggest(Proyecto $proyecto): JsonResponse
    {
        try {
            $sugerencias = $this->etiquetaService->getSuggestions($proyecto, 15);

            return response()->json([
                'success' => true,
                'sugerencias' => $sugerencias,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sugerencias: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Busca etiquetas para autocompletado.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        try {
            $etiquetas = $this->etiquetaRepository->autocomplete($request->q, 20);

            return response()->json([
                'success' => true,
                'etiquetas' => $etiquetas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reordena las etiquetas del proyecto.
     */
    public function reorder(Request $request, Proyecto $proyecto): JsonResponse
    {
        $this->authorize('proyectos.manage_tags');

        $request->validate([
            'etiquetas' => 'required|array',
            'etiquetas.*.id' => 'required|exists:etiquetas,id',
            'etiquetas.*.orden' => 'required|integer|min:0',
        ]);

        try {
            foreach ($request->etiquetas as $item) {
                $proyecto->etiquetas()
                    ->updateExistingPivot($item['id'], ['orden' => $item['orden']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Orden de etiquetas actualizado exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reordenar etiquetas: ' . $e->getMessage(),
            ], 500);
        }
    }
}