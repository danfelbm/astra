<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\AdminController;
use Modules\Core\Traits\HasAdvancedFilters;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Models\Entregable;
use Modules\Proyectos\Services\EntregableService;
use Modules\Proyectos\Repositories\EntregableRepository;
use Modules\Proyectos\Http\Requests\Admin\StoreEntregableRequest;
use Modules\Proyectos\Http\Requests\Admin\UpdateEntregableRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\Inertia;

class EntregableController extends AdminController
{
    use HasAdvancedFilters;

    public function __construct(
        private EntregableService $entregableService,
        private EntregableRepository $entregableRepository
    ) {}

    /**
     * Muestra la lista de entregables de un hito.
     */
    public function index(Request $request, Proyecto $proyecto, Hito $hito): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.view'), 403, 'No tienes permisos para ver entregables');

        $hito->load(['responsable:id,name,email']);

        $entregables = $this->entregableRepository->getByHito(
            $hito->id,
            $request->all()
        );

        // Obtener resumen por usuario
        $resumenUsuarios = $this->entregableRepository->getResumenPorUsuario($hito->id);

        return Inertia::render('Modules/Proyectos/Admin/Entregables/Index', [
            'proyecto' => $proyecto,
            'hito' => $hito,
            'entregables' => $entregables,
            'filters' => $request->only(['search', 'estado', 'prioridad', 'responsable_id']),
            'resumenUsuarios' => $resumenUsuarios,
            'estados' => [
                ['value' => 'pendiente', 'label' => 'Pendiente'],
                ['value' => 'en_progreso', 'label' => 'En Progreso'],
                ['value' => 'completado', 'label' => 'Completado'],
                ['value' => 'cancelado', 'label' => 'Cancelado'],
            ],
            'prioridades' => [
                ['value' => 'baja', 'label' => 'Baja', 'color' => 'blue'],
                ['value' => 'media', 'label' => 'Media', 'color' => 'yellow'],
                ['value' => 'alta', 'label' => 'Alta', 'color' => 'red'],
            ],
            'canCreate' => auth()->user()->can('entregables.create'),
            'canEdit' => auth()->user()->can('entregables.edit'),
            'canDelete' => auth()->user()->can('entregables.delete'),
            'canComplete' => auth()->user()->can('entregables.complete'),
            'canAssign' => auth()->user()->can('entregables.assign'),
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo entregable.
     */
    public function create(Request $request, Proyecto $proyecto, Hito $hito): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.create'), 403, 'No tienes permisos para crear entregables');

        $proyecto->load(['participantes.user:id,name,email']);

        $usuarios = $proyecto->participantes()
            ->with('user:id,name,email')
            ->get()
            ->map(fn($p) => [
                'id' => $p->user->id,
                'name' => $p->user->name,
                'email' => $p->user->email
            ]);

        return Inertia::render('Modules/Proyectos/Admin/Entregables/Create', [
            'proyecto' => $proyecto,
            'hito' => $hito,
            'usuarios' => $usuarios,
            'estados' => [
                ['value' => 'pendiente', 'label' => 'Pendiente'],
                ['value' => 'en_progreso', 'label' => 'En Progreso'],
                ['value' => 'completado', 'label' => 'Completado'],
                ['value' => 'cancelado', 'label' => 'Cancelado'],
            ],
            'prioridades' => [
                ['value' => 'baja', 'label' => 'Baja', 'color' => 'blue'],
                ['value' => 'media', 'label' => 'Media', 'color' => 'yellow'],
                ['value' => 'alta', 'label' => 'Alta', 'color' => 'red'],
            ],
            'roles' => [
                ['value' => 'responsable', 'label' => 'Responsable'],
                ['value' => 'colaborador', 'label' => 'Colaborador'],
                ['value' => 'revisor', 'label' => 'Revisor'],
            ],
            'siguienteOrden' => $hito->entregables()->max('orden') + 1 ?? 1
        ]);
    }

    /**
     * Guarda un nuevo entregable en la base de datos.
     */
    public function store(StoreEntregableRequest $request, Proyecto $proyecto, Hito $hito): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.create'), 403, 'No tienes permisos para crear entregables');

        $data = $request->validated();
        $data['hito_id'] = $hito->id;

        $result = $this->entregableService->create($data);

        if ($result['success']) {
            // Los usuarios ya se asignan en el service/repository
            // No es necesario volver a asignarlos aquí

            // Actualizar porcentaje del hito
            $hito->calcularPorcentajeCompletado();

            return redirect()
                ->route('admin.proyectos.hitos.entregables.index', [$proyecto, $hito])
                ->with('success', 'Entregable creado exitosamente');
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Muestra los detalles de un entregable específico.
     */
    public function show(Request $request, Proyecto $proyecto, Hito $hito, Entregable $entregable): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.view'), 403, 'No tienes permisos para ver este entregable');

        $entregable->load([
            'responsable:id,name,email',
            'usuarios' => function ($query) {
                $query->select('users.id', 'users.name', 'users.email')
                      ->withPivot('rol');
            },
            'completadoPor:id,name,email',
            'evidencias' => function ($query) {
                $query->with([
                    'usuario:id,name,email',
                    'obligacion:id,titulo,contrato_id',
                    'obligacion.contrato'
                ]);
            }
        ]);

        // Preparar usuarios asignados para el frontend
        $usuariosAsignados = $entregable->usuarios->map(fn($u) => [
            'user_id' => $u->id,
            'user' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar' => $u->avatar ?? null
            ],
            'rol' => $u->pivot->rol,
            'created_at' => $u->pivot->created_at
        ])->toArray();

        // Obtener actividades del entregable
        $actividades = [];
        // TODO: Implementar registro de actividades si es necesario

        return Inertia::render('Modules/Proyectos/Admin/Entregables/Show', [
            'proyecto' => $proyecto,
            'hito' => $hito,
            'entregable' => $entregable,
            'usuariosAsignados' => $usuariosAsignados,
            'actividades' => $actividades,
            'canEdit' => auth()->user()->can('entregables.edit'),
            'canDelete' => auth()->user()->can('entregables.delete'),
            'canComplete' => auth()->user()->can('entregables.complete'),
            'canAssign' => auth()->user()->can('entregables.assign'),
        ]);
    }

    /**
     * Muestra el formulario para editar un entregable.
     */
    public function edit(Request $request, Proyecto $proyecto, Hito $hito, Entregable $entregable): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.edit'), 403, 'No tienes permisos para editar entregables');

        $proyecto->load(['participantes.user:id,name,email']);

        $entregable->load([
            'responsable:id,name,email',
            'usuarios' => function ($query) {
                $query->select('users.id', 'users.name', 'users.email')
                      ->withPivot('rol');
            }
        ]);

        $usuarios = $proyecto->participantes()
            ->with('user:id,name,email')
            ->get()
            ->map(fn($p) => [
                'id' => $p->user->id,
                'name' => $p->user->name,
                'email' => $p->user->email
            ]);

        // Preparar usuarios asignados actuales con información completa del usuario
        $usuariosAsignados = $entregable->usuarios->map(fn($u) => [
            'user_id' => $u->id,
            'user' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar' => $u->avatar ?? null
            ],
            'rol' => $u->pivot->rol
        ])->toArray();

        return Inertia::render('Modules/Proyectos/Admin/Entregables/Edit', [
            'proyecto' => $proyecto,
            'hito' => $hito,
            'entregable' => $entregable,
            'usuarios' => $usuarios,
            'usuariosAsignados' => $usuariosAsignados,
            'estados' => [
                ['value' => 'pendiente', 'label' => 'Pendiente'],
                ['value' => 'en_progreso', 'label' => 'En Progreso'],
                ['value' => 'completado', 'label' => 'Completado'],
                ['value' => 'cancelado', 'label' => 'Cancelado'],
            ],
            'prioridades' => [
                ['value' => 'baja', 'label' => 'Baja', 'color' => 'blue'],
                ['value' => 'media', 'label' => 'Media', 'color' => 'yellow'],
                ['value' => 'alta', 'label' => 'Alta', 'color' => 'red'],
            ],
            'roles' => [
                ['value' => 'responsable', 'label' => 'Responsable'],
                ['value' => 'colaborador', 'label' => 'Colaborador'],
                ['value' => 'revisor', 'label' => 'Revisor'],
            ]
        ]);
    }

    /**
     * Actualiza un entregable en la base de datos.
     */
    public function update(UpdateEntregableRequest $request, Proyecto $proyecto, Hito $hito, Entregable $entregable): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.edit'), 403, 'No tienes permisos para editar entregables');

        $data = $request->validated();

        $result = $this->entregableService->update($entregable, $data);

        if ($result['success']) {
            // Los usuarios ya se actualizan en el service/repository
            // No es necesario volver a asignarlos aquí

            // Actualizar porcentaje del hito
            $hito->calcularPorcentajeCompletado();

            return redirect()
                ->route('admin.proyectos.hitos.entregables.show', [$proyecto, $hito, $entregable])
                ->with('success', 'Entregable actualizado exitosamente');
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Elimina un entregable de la base de datos.
     */
    public function destroy(Request $request, Proyecto $proyecto, Hito $hito, Entregable $entregable): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.delete'), 403, 'No tienes permisos para eliminar entregables');

        $result = $this->entregableService->delete($entregable);

        if ($result['success']) {
            // Actualizar porcentaje del hito
            $hito->calcularPorcentajeCompletado();

            return redirect()
                ->route('admin.proyectos.hitos.entregables.index', [$proyecto, $hito])
                ->with('success', 'Entregable eliminado exitosamente');
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Marca un entregable como completado.
     */
    public function completar(Request $request, Proyecto $proyecto, Hito $hito, Entregable $entregable): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.complete'), 403, 'No tienes permisos para completar entregables');

        $request->validate([
            'notas' => 'nullable|string|max:1000'
        ]);

        $result = $this->entregableService->marcarComoCompletado(
            $entregable,
            auth()->id(),
            $request->notas
        );

        if ($result['success']) {
            // Actualizar porcentaje del hito
            $hito->calcularPorcentajeCompletado();

            return redirect()
                ->back()
                ->with('success', 'Entregable completado exitosamente');
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Actualiza el orden de los entregables.
     */
    public function reordenar(Request $request, Proyecto $proyecto, Hito $hito): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.edit'), 403, 'No tienes permisos para reordenar entregables');

        $request->validate([
            'entregables' => 'required|array',
            'entregables.*.id' => 'required|exists:entregables,id',
            'entregables.*.orden' => 'required|integer|min:0'
        ]);

        $result = $this->entregableRepository->reordenar($request->entregables);

        if ($result) {
            return redirect()
                ->back()
                ->with('success', 'Orden actualizado exitosamente');
        }

        return redirect()
            ->back()
            ->with('error', 'Error al actualizar el orden');
    }

    /**
     * Duplica un entregable con sus asignaciones.
     */
    public function duplicar(Request $request, Proyecto $proyecto, Hito $hito, Entregable $entregable): RedirectResponse
    {
        $this->authorize('create', Entregable::class);
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.view'), 403, 'No tienes permisos para ver este entregable');

        $nuevoEntregable = $this->entregableRepository->duplicar($entregable->id, $hito->id);

        if ($nuevoEntregable) {
            return redirect()
                ->route('admin.proyectos.hitos.entregables.show', [$proyecto, $hito, $nuevoEntregable])
                ->with('success', 'Entregable duplicado exitosamente');
        }

        return redirect()
            ->back()
            ->with('error', 'Error al duplicar el entregable');
    }
}