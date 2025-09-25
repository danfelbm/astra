<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Core\Traits\HasAdvancedFilters;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\CampoPersonalizado;
use Modules\Proyectos\Models\Etiqueta;
use Modules\Proyectos\Models\CategoriaEtiqueta;
use Modules\Proyectos\Http\Requests\Admin\StoreProyectoRequest;
use Modules\Proyectos\Http\Requests\Admin\UpdateProyectoRequest;
use Modules\Proyectos\Services\ProyectoService;
use Modules\Proyectos\Repositories\ProyectoRepository;
use Modules\Core\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ProyectoController extends AdminController
{
    use HasAdvancedFilters;

    public function __construct(
        private ProyectoService $service,
        private ProyectoRepository $repository
    ) {
        parent::__construct();
    }

    /**
     * Muestra la lista de proyectos.
     */
    public function index(Request $request): Response
    {
        $proyectos = $this->repository->getAllPaginated($request);

        return Inertia::render('Modules/Proyectos/Admin/Proyectos/Index', [
            'proyectos' => $proyectos,
            'filters' => $request->only(['search', 'estado', 'prioridad', 'responsable_id']),
            'canCreate' => auth()->user()->can('proyectos.create'),
            'canEdit' => auth()->user()->can('proyectos.edit'),
            'canDelete' => auth()->user()->can('proyectos.delete'),
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo proyecto.
     */
    public function create(): Response
    {
        $usuarios = User::select('id', 'name', 'email')->orderBy('name')->get();
        $camposPersonalizados = CampoPersonalizado::paraProyectos()->activos()->ordenado()->get();

        // Cargar etiquetas y categorías para el selector
        $categorias = CategoriaEtiqueta::with('etiquetas')
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        return Inertia::render('Modules/Proyectos/Admin/Proyectos/Create', [
            'usuarios' => $usuarios,
            'camposPersonalizados' => $camposPersonalizados,
            'categorias' => $categorias,
            'estados' => config('proyectos.estados'),
            'prioridades' => config('proyectos.prioridades'),
        ]);
    }

    /**
     * Almacena un nuevo proyecto.
     */
    public function store(StoreProyectoRequest $request): RedirectResponse
    {
        $result = $this->service->create($request->validated());

        return redirect()
            ->route('admin.proyectos.index')
            ->with('success', $result['message']);
    }

    /**
     * Muestra los detalles de un proyecto.
     */
    public function show(Proyecto $proyecto): Response
    {
        $proyecto->load([
            'responsable',
            'creador',
            'camposPersonalizados.campoPersonalizado',
            'etiquetas.categoria', // Cargar etiquetas con sus categorías
            'contratos' => function ($query) {
                $query->orderBy('fecha_inicio', 'desc');
            }
        ]);

        // Cargar categorías disponibles si el usuario puede gestionar etiquetas
        $categorias = auth()->user()->can('proyectos.manage_tags')
            ? CategoriaEtiqueta::with('etiquetas')
                ->where('activo', true)
                ->orderBy('orden')
                ->get()
            : null;

        return Inertia::render('Modules/Proyectos/Admin/Proyectos/Show', [
            'proyecto' => $proyecto,
            'categorias' => $categorias,
            'canEdit' => auth()->user()->can('proyectos.edit'),
            'canDelete' => auth()->user()->can('proyectos.delete'),
            'canManageTags' => auth()->user()->can('proyectos.manage_tags'),
            'canViewContracts' => auth()->user()->can('contratos.view'),
            'canCreateContracts' => auth()->user()->can('contratos.create'),
        ]);
    }

    /**
     * Muestra el formulario para editar un proyecto.
     */
    public function edit(Proyecto $proyecto): Response
    {
        $usuarios = User::select('id', 'name', 'email')->orderBy('name')->get();
        $camposPersonalizados = CampoPersonalizado::paraProyectos()->activos()->ordenado()->get();
        $proyecto->load(['camposPersonalizados', 'etiquetas.categoria']);

        // Preparar valores de campos personalizados
        $valoresCampos = [];
        foreach ($camposPersonalizados as $campo) {
            // Usar el ID como clave en lugar del slug
            $valoresCampos[$campo->id] = $campo->getValorParaProyecto($proyecto->id);
        }

        // Cargar etiquetas y categorías para el selector
        $categorias = CategoriaEtiqueta::with('etiquetas')
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        return Inertia::render('Modules/Proyectos/Admin/Proyectos/Edit', [
            'proyecto' => $proyecto,
            'usuarios' => $usuarios,
            'camposPersonalizados' => $camposPersonalizados,
            'valoresCampos' => $valoresCampos,
            'categorias' => $categorias,
            'estados' => config('proyectos.estados'),
            'prioridades' => config('proyectos.prioridades'),
        ]);
    }

    /**
     * Actualiza un proyecto existente.
     */
    public function update(UpdateProyectoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        $result = $this->service->update($proyecto, $request->validated());

        return redirect()
            ->route('admin.proyectos.index')
            ->with('success', $result['message']);
    }

    /**
     * Elimina un proyecto.
     */
    public function destroy(Proyecto $proyecto): RedirectResponse
    {
        $this->service->delete($proyecto);

        return redirect()
            ->route('admin.proyectos.index')
            ->with('success', 'Proyecto eliminado exitosamente');
    }

    /**
     * Cambia el estado activo/inactivo de un proyecto.
     */
    public function toggleStatus(Proyecto $proyecto): RedirectResponse
    {
        $proyecto->activo = !$proyecto->activo;
        $proyecto->save();

        $estado = $proyecto->activo ? 'activado' : 'desactivado';

        return redirect()
            ->back()
            ->with('success', "Proyecto {$estado} exitosamente");
    }

    /**
     * Asigna un responsable al proyecto.
     */
    public function asignarResponsable(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $request->validate([
            'responsable_id' => 'required|exists:users,id'
        ]);

        $this->service->asignarResponsable($proyecto, $request->responsable_id);

        return redirect()
            ->back()
            ->with('success', 'Responsable asignado exitosamente');
    }
}