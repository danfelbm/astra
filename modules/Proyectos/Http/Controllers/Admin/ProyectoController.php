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
use Illuminate\Http\JsonResponse;

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
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.view'), 403, 'No tienes permisos para ver proyectos');

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
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.create'), 403, 'No tienes permisos para crear proyectos');

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
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.view'), 403, 'No tienes permisos para ver este proyecto');

        $proyecto->load([
            'responsable',
            'creador',
            'camposPersonalizados.campoPersonalizado',
            'etiquetas.categoria', // Cargar etiquetas con sus categorías
            'contratos' => function ($query) {
                $query->orderBy('fecha_inicio', 'desc');
            },
            'hitos' => function ($query) {
                $query->with(['responsable', 'entregables' => function ($q) {
                    $q->with(['responsable', 'usuarios'])->orderBy('orden');
                }])->orderBy('orden');
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
            'canViewHitos' => auth()->user()->can('hitos.view'),
            'canCreateHitos' => auth()->user()->can('hitos.create'),
            'canEditHitos' => auth()->user()->can('hitos.edit'),
            'canDeleteHitos' => auth()->user()->can('hitos.delete'),
            'canManageEntregables' => auth()->user()->can('hitos.manage_deliverables'),
            'estadisticasHitos' => $proyecto->getEstadisticasHitos(),
        ]);
    }

    /**
     * Muestra el formulario para editar un proyecto.
     */
    public function edit(Proyecto $proyecto): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.edit'), 403, 'No tienes permisos para editar este proyecto');

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
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.delete'), 403, 'No tienes permisos para eliminar proyectos');

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
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.edit'), 403, 'No tienes permisos para cambiar el estado del proyecto');

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
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.edit'), 403, 'No tienes permisos para asignar responsables');

        $request->validate([
            'responsable_id' => 'required|exists:users,id'
        ]);

        $this->service->asignarResponsable($proyecto, $request->responsable_id);

        return redirect()
            ->back()
            ->with('success', 'Responsable asignado exitosamente');
    }

    /**
     * Busca usuarios disponibles para asignar a proyectos.
     * Endpoint usado por el componente AddUsersModal.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.view'), 403, 'No tienes permisos para buscar usuarios');

        $query = User::query()
            ->select('id', 'name', 'email', 'documento_identidad', 'telefono');

        // Aplicar búsqueda si existe
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('documento_identidad', 'like', "%{$search}%")
                  ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        // Excluir usuarios si se especifica
        if ($request->has('excluded_ids') && is_array($request->excluded_ids)) {
            $query->whereNotIn('id', $request->excluded_ids);
        }

        // Ordenar por nombre
        $query->orderBy('name');

        // Paginar resultados
        $usuarios = $query->paginate($request->get('per_page', 20));

        // Retornar en formato esperado por AddUsersModal
        return response()->json([
            'users' => $usuarios->items(),
            'current_page' => $usuarios->currentPage(),
            'last_page' => $usuarios->lastPage(),
            'per_page' => $usuarios->perPage(),
            'total' => $usuarios->total(),
        ]);
    }
}