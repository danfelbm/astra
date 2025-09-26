<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Proyectos\Models\CategoriaEtiqueta;
use Modules\Proyectos\Services\CategoriaEtiquetaService;
use Modules\Proyectos\Repositories\CategoriaEtiquetaRepository;
use Modules\Proyectos\Http\Requests\Admin\StoreCategoriaEtiquetaRequest;
use Modules\Proyectos\Http\Requests\Admin\UpdateCategoriaEtiquetaRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaEtiquetaController extends AdminController
{
    public function __construct(
        private CategoriaEtiquetaService $service,
        private CategoriaEtiquetaRepository $repository
    ) {
        parent::__construct();
    }

    /**
     * Muestra la lista de categorías de etiquetas.
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('categorias_etiquetas.view'), 403, 'No tienes permisos para ver categorías de etiquetas');

        $categorias = $this->repository->getAllPaginated($request);
        $estadisticas = $this->repository->getEstadisticas();

        return Inertia::render('Modules/Proyectos/Admin/CategoriaEtiquetas/Index', [
            'categorias' => $categorias,
            'estadisticas' => $estadisticas,
            'filters' => $request->only(['search', 'activo']),
            'colores' => $this->service->getColoresDisponibles(),
            'iconos' => $this->service->getIconosSugeridos(),
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva categoría.
     */
    public function create(): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('categorias_etiquetas.create'), 403, 'No tienes permisos para crear categorías de etiquetas');

        return Inertia::render('Modules/Proyectos/Admin/CategoriaEtiquetas/Form', [
            'colores' => $this->service->getColoresDisponibles(),
            'iconos' => $this->service->getIconosSugeridos(),
        ]);
    }

    /**
     * Almacena una nueva categoría de etiquetas.
     */
    public function store(StoreCategoriaEtiquetaRequest $request): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('categorias_etiquetas.create'), 403, 'No tienes permisos para crear categorías de etiquetas');

        $categoria = $this->service->create($request->validated());

        return redirect()
            ->route('admin.categorias-etiquetas.index')
            ->with('success', 'Categoría de etiquetas creada exitosamente');
    }

    /**
     * Muestra los detalles de una categoría.
     */
    public function show(CategoriaEtiqueta $categoriaEtiqueta): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('categorias_etiquetas.view'), 403, 'No tienes permisos para ver categorías de etiquetas');

        $categoriaEtiqueta->loadCount('etiquetas');
        $categoriaEtiqueta->load(['etiquetas' => function ($query) {
            $query->orderBy('usos_count', 'desc')->limit(10);
        }]);

        return Inertia::render('Modules/Proyectos/Admin/CategoriaEtiquetas/Show', [
            'categoria' => $categoriaEtiqueta,
            'canEdit' => auth()->user()->can('categorias_etiquetas.edit'),
            'canDelete' => auth()->user()->can('categorias_etiquetas.delete'),
        ]);
    }

    /**
     * Muestra el formulario para editar una categoría.
     */
    public function edit(CategoriaEtiqueta $categoriaEtiqueta): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('categorias_etiquetas.edit'), 403, 'No tienes permisos para editar categorías de etiquetas');

        return Inertia::render('Modules/Proyectos/Admin/CategoriaEtiquetas/Form', [
            'categoria' => $categoriaEtiqueta,
            'colores' => $this->service->getColoresDisponibles(),
            'iconos' => $this->service->getIconosSugeridos(),
        ]);
    }

    /**
     * Actualiza una categoría de etiquetas.
     */
    public function update(UpdateCategoriaEtiquetaRequest $request, CategoriaEtiqueta $categoriaEtiqueta): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('categorias_etiquetas.edit'), 403, 'No tienes permisos para editar categorías de etiquetas');

        $categoria = $this->service->update($categoriaEtiqueta, $request->validated());

        return redirect()
            ->route('admin.categorias-etiquetas.index')
            ->with('success', 'Categoría de etiquetas actualizada exitosamente');
    }

    /**
     * Elimina una categoría de etiquetas.
     */
    public function destroy(Request $request, CategoriaEtiqueta $categoriaEtiqueta): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('categorias_etiquetas.delete'), 403, 'No tienes permisos para eliminar categorías de etiquetas');

        $request->validate([
            'categoria_destino_id' => 'nullable|exists:categorias_etiquetas,id|different:' . $categoriaEtiqueta->id,
        ]);

        try {
            $this->service->delete($categoriaEtiqueta, $request->categoria_destino_id);

            return redirect()
                ->route('admin.categorias-etiquetas.index')
                ->with('success', 'Categoría de etiquetas eliminada exitosamente');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cambia el estado activo/inactivo de una categoría.
     */
    public function toggleActive(CategoriaEtiqueta $categoriaEtiqueta): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('categorias_etiquetas.edit'), 403, 'No tienes permisos para editar categorías de etiquetas');

        $categoria = $this->service->toggleActive($categoriaEtiqueta);
        $estado = $categoria->activo ? 'activada' : 'desactivada';

        return redirect()
            ->back()
            ->with('success', "Categoría {$estado} exitosamente");
    }

    /**
     * Reordena las categorías.
     */
    public function reorder(Request $request): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('categorias_etiquetas.edit'), 403, 'No tienes permisos para editar categorías de etiquetas');

        $request->validate([
            'categorias' => 'required|array',
            'categorias.*.id' => 'required|exists:categorias_etiquetas,id',
            'categorias.*.orden' => 'required|integer|min:0',
        ]);

        $this->service->reorder($request->categorias);

        return redirect()
            ->back()
            ->with('success', 'Orden de categorías actualizado exitosamente');
    }

    /**
     * Fusiona dos categorías.
     */
    public function merge(Request $request, CategoriaEtiqueta $categoriaEtiqueta): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('categorias_etiquetas.delete'), 403, 'No tienes permisos para eliminar categorías de etiquetas');

        $request->validate([
            'categoria_destino_id' => 'required|exists:categorias_etiquetas,id|different:' . $categoriaEtiqueta->id,
        ]);

        $categoriaDestino = CategoriaEtiqueta::find($request->categoria_destino_id);
        $this->service->merge($categoriaEtiqueta, $categoriaDestino);

        return redirect()
            ->route('admin.categorias-etiquetas.index')
            ->with('success', 'Categorías fusionadas exitosamente');
    }
}