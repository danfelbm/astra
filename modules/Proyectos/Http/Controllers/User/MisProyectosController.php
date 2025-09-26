<?php

namespace Modules\Proyectos\Http\Controllers\User;

use Modules\Core\Http\Controllers\Base\UserController;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\CampoPersonalizado;
use Modules\Proyectos\Http\Requests\User\UpdateMiProyectoRequest;
use Modules\Proyectos\Services\ProyectoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class MisProyectosController extends UserController
{
    public function __construct(
        private ProyectoService $service
    ) {
        parent::__construct();
    }

    /**
     * Muestra la lista de proyectos del usuario.
     */
    public function index(Request $request): Response
    {
        $proyectos = Proyecto::query()
            ->where(function ($query) {
                $query->where('responsable_id', auth()->id())
                      ->orWhere('created_by', auth()->id());
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            })
            ->when($request->estado, function ($query, $estado) {
                $query->where('estado', $estado);
            })
            ->when($request->prioridad, function ($query, $prioridad) {
                $query->where('prioridad', $prioridad);
            })
            ->with(['responsable', 'etiquetas.categoria'])
            ->orderBy('prioridad', 'desc')
            ->orderBy('fecha_inicio', 'asc')
            ->paginate(12);

        return Inertia::render('Modules/Proyectos/User/MisProyectos/Index', [
            'proyectos' => $proyectos,
            'filters' => $request->only(['search', 'estado', 'prioridad']),
            'estados' => config('proyectos.estados'),
            'prioridades' => config('proyectos.prioridades'),
            'canCreate' => auth()->user()->can('proyectos.create_own'),
            'canEdit' => auth()->user()->can('proyectos.edit_own'),
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo proyecto.
     */
    public function create(): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.create_own'), 403, 'No tienes permiso para crear proyectos');

        $camposPersonalizados = CampoPersonalizado::activos()->ordenado()->get();

        // Cargar etiquetas y categorías para el selector
        $categorias = \Modules\Proyectos\Models\CategoriaEtiqueta::with('etiquetas')
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        return Inertia::render('Modules/Proyectos/User/MisProyectos/Create', [
            'camposPersonalizados' => $camposPersonalizados,
            'categorias' => $categorias,
            'estados' => ['planificacion' => 'Planificación', 'en_progreso' => 'En Progreso'],
            'prioridades' => config('proyectos.prioridades'),
        ]);
    }

    /**
     * Almacena un nuevo proyecto del usuario.
     */
    public function store(Request $request): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.create_own'), 403, 'No tienes permiso para crear proyectos');

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
            'prioridad' => 'required|in:baja,media,alta,critica',
            'etiquetas' => 'nullable|array|max:10',
            'etiquetas.*' => 'exists:etiquetas,id',
            'campos_personalizados' => 'nullable|array',
        ]);

        // Asignar el usuario actual como responsable y creador
        $validated['responsable_id'] = auth()->id();
        $validated['created_by'] = auth()->id();
        $validated['estado'] = 'planificacion';

        $result = $this->service->create($validated);

        return redirect()
            ->route('miembro.mis-proyectos.index')
            ->with('success', 'Proyecto creado exitosamente');
    }

    /**
     * Muestra los detalles de un proyecto del usuario.
     */
    public function show(Proyecto $proyecto): Response
    {
        // Verificar permisos y acceso al proyecto
        abort_unless(auth()->user()->can('proyectos.view_own'), 403, 'No tienes permiso para ver proyectos');
        abort_unless(
            $proyecto->responsable_id === auth()->id() || $proyecto->created_by === auth()->id(),
            403,
            'No tienes acceso a este proyecto'
        );

        $proyecto->load(['responsable', 'creador', 'camposPersonalizados.campoPersonalizado', 'etiquetas.categoria']);

        return Inertia::render('Modules/Proyectos/User/MisProyectos/Show', [
            'proyecto' => $proyecto,
            'canEdit' => auth()->user()->can('proyectos.edit_own') &&
                        ($proyecto->responsable_id === auth()->id() || $proyecto->created_by === auth()->id()),
            'canDelete' => auth()->user()->can('proyectos.delete_own') &&
                          ($proyecto->responsable_id === auth()->id() || $proyecto->created_by === auth()->id()),
        ]);
    }

    /**
     * Muestra el formulario para editar un proyecto del usuario.
     */
    public function edit(Proyecto $proyecto): Response
    {
        // Verificar permisos y acceso al proyecto
        abort_unless(auth()->user()->can('proyectos.edit_own'), 403, 'No tienes permiso para editar proyectos');
        abort_unless(
            $proyecto->responsable_id === auth()->id() || $proyecto->created_by === auth()->id(),
            403,
            'No tienes acceso a este proyecto'
        );

        $camposPersonalizados = CampoPersonalizado::activos()->ordenado()->get();
        $proyecto->load(['camposPersonalizados', 'etiquetas.categoria']);

        // Preparar valores de campos personalizados
        $valoresCampos = [];
        foreach ($camposPersonalizados as $campo) {
            $valoresCampos[$campo->slug] = $campo->getValorParaProyecto($proyecto->id);
        }

        // Cargar etiquetas y categorías para el selector
        $categorias = \Modules\Proyectos\Models\CategoriaEtiqueta::with('etiquetas')
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        return Inertia::render('Modules/Proyectos/User/MisProyectos/Edit', [
            'proyecto' => $proyecto,
            'camposPersonalizados' => $camposPersonalizados,
            'valoresCampos' => $valoresCampos,
            'categorias' => $categorias,
            'estados' => config('proyectos.estados'),
            'prioridades' => config('proyectos.prioridades'),
        ]);
    }

    /**
     * Actualiza un proyecto del usuario.
     */
    public function update(UpdateMiProyectoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        // Verificar permisos y acceso al proyecto
        abort_unless(auth()->user()->can('proyectos.edit_own'), 403, 'No tienes permiso para editar proyectos');
        abort_unless(
            $proyecto->responsable_id === auth()->id() || $proyecto->created_by === auth()->id(),
            403,
            'No tienes acceso a este proyecto'
        );

        $result = $this->service->update($proyecto, $request->validated());

        return redirect()
            ->route('miembro.mis-proyectos.index')
            ->with('success', 'Proyecto actualizado exitosamente');
    }

    /**
     * Cambia el estado del proyecto.
     */
    public function cambiarEstado(Request $request, Proyecto $proyecto): RedirectResponse
    {
        // Verificar permisos y acceso al proyecto
        abort_unless(auth()->user()->can('proyectos.edit_own'), 403, 'No tienes permiso para editar proyectos');
        abort_unless(
            $proyecto->responsable_id === auth()->id() || $proyecto->created_by === auth()->id(),
            403,
            'No tienes permiso para cambiar el estado de este proyecto'
        );

        $request->validate([
            'estado' => 'required|in:planificacion,en_progreso,pausado,completado,cancelado'
        ]);

        $proyecto->estado = $request->estado;
        $proyecto->updated_by = auth()->id();
        $proyecto->save();

        return redirect()
            ->back()
            ->with('success', 'Estado del proyecto actualizado exitosamente');
    }

    /**
     * Muestra los campos personalizados del proyecto.
     */
    public function camposPersonalizados(Proyecto $proyecto): Response
    {
        // Verificar permisos y acceso al proyecto
        abort_unless(auth()->user()->can('proyectos.view_own'), 403, 'No tienes permiso para ver proyectos');
        abort_unless(
            $proyecto->responsable_id === auth()->id() || $proyecto->created_by === auth()->id(),
            403,
            'No tienes acceso a este proyecto'
        );

        $camposPersonalizados = CampoPersonalizado::activos()->ordenado()->get();
        $proyecto->load('camposPersonalizados');

        // Preparar valores de campos personalizados
        $valoresCampos = [];
        foreach ($camposPersonalizados as $campo) {
            $valoresCampos[$campo->slug] = $campo->getValorParaProyecto($proyecto->id);
        }

        return Inertia::render('Modules/Proyectos/User/CamposPersonalizados', [
            'proyecto' => $proyecto,
            'camposPersonalizados' => $camposPersonalizados,
            'valoresCampos' => $valoresCampos,
            'canEdit' => auth()->user()->can('proyectos.edit_own') &&
                        ($proyecto->responsable_id === auth()->id() || $proyecto->created_by === auth()->id()),
        ]);
    }

    /**
     * Guarda los valores de campos personalizados.
     */
    public function guardarCamposPersonalizados(Request $request, Proyecto $proyecto): RedirectResponse
    {
        // Verificar permisos y acceso al proyecto
        abort_unless(auth()->user()->can('proyectos.edit_own'), 403, 'No tienes permiso para editar proyectos');
        abort_unless(
            $proyecto->responsable_id === auth()->id() || $proyecto->created_by === auth()->id(),
            403,
            'No tienes permiso para editar este proyecto'
        );

        $this->service->actualizarCamposPersonalizados($proyecto, $request->all());

        return redirect()
            ->back()
            ->with('success', 'Campos personalizados actualizados exitosamente');
    }

    /**
     * Verifica si el usuario puede acceder al proyecto.
     */
    private function userCanAccessProject(Proyecto $proyecto): bool
    {
        return $proyecto->responsable_id === auth()->id() ||
               $proyecto->created_by === auth()->id();
    }

    /**
     * Verifica si el usuario puede editar el proyecto.
     */
    private function userCanEditProject(Proyecto $proyecto): bool
    {
        if (!auth()->user()->can('proyectos.edit_own')) {
            return false;
        }

        return $this->userCanAccessProject($proyecto);
    }
}