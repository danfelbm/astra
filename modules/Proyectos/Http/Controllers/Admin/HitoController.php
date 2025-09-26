<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\AdminController;
use Modules\Core\Traits\HasAdvancedFilters;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Services\HitoService;
use Modules\Proyectos\Repositories\HitoRepository;
use Modules\Proyectos\Http\Requests\Admin\StoreHitoRequest;
use Modules\Proyectos\Http\Requests\Admin\UpdateHitoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\Inertia;

class HitoController extends AdminController
{
    use HasAdvancedFilters;

    public function __construct(
        private HitoService $hitoService,
        private HitoRepository $hitoRepository
    ) {}

    /**
     * Muestra la lista de hitos de un proyecto.
     */
    public function index(Request $request, Proyecto $proyecto): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('hitos.view'), 403, 'No tienes permisos para ver hitos');

        $proyecto->load(['responsable', 'etiquetas.categoria']);

        $hitos = $this->hitoRepository->getByProyecto(
            $proyecto->id,
            $request->all()
        );

        return Inertia::render('Modules/Proyectos/Admin/Hitos/Index', [
            'proyecto' => $proyecto,
            'hitos' => $hitos,
            'filters' => $request->only(['search', 'estado', 'responsable_id']),
            'estadisticas' => $proyecto->getEstadisticasHitos(),
            'timeline' => $this->hitoRepository->getTimelineData($proyecto->id),
            'canCreate' => auth()->user()->can('hitos.create'),
            'canEdit' => auth()->user()->can('hitos.edit'),
            'canDelete' => auth()->user()->can('hitos.delete'),
            'canManageDeliverables' => auth()->user()->can('hitos.manage_deliverables'),
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo hito.
     */
    public function create(Request $request, Proyecto $proyecto): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('hitos.create'), 403, 'No tienes permisos para crear hitos');

        $proyecto->load(['participantes', 'responsable']);

        // Obtener posibles responsables del proyecto
        $responsables = $proyecto->participantes()
            ->with('user:id,name,email')
            ->get()
            ->map(fn($p) => [
                'id' => $p->user->id,
                'name' => $p->user->name,
                'email' => $p->user->email
            ]);

        return Inertia::render('Modules/Proyectos/Admin/Hitos/Create', [
            'proyecto' => $proyecto,
            'responsables' => $responsables,
            'estados' => [
                ['value' => 'pendiente', 'label' => 'Pendiente'],
                ['value' => 'en_progreso', 'label' => 'En Progreso'],
                ['value' => 'completado', 'label' => 'Completado'],
                ['value' => 'cancelado', 'label' => 'Cancelado'],
            ],
            'siguienteOrden' => $proyecto->hitos()->max('orden') + 1 ?? 1
        ]);
    }

    /**
     * Guarda un nuevo hito en la base de datos.
     */
    public function store(StoreHitoRequest $request, Proyecto $proyecto): RedirectResponse
    {
        // El FormRequest ya verifica permisos con can('hitos.create')

        // Combinar proyecto_id con los datos validados
        $data = array_merge(
            $request->validated(),
            ['proyecto_id' => $proyecto->id]
        );

        $result = $this->hitoService->create($data);

        if ($result['success']) {
            return redirect()
                ->route('admin.proyectos.hitos.index', $proyecto)
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Muestra los detalles de un hito específico.
     */
    public function show(Request $request, Proyecto $proyecto, Hito $hito): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('hitos.view'), 403, 'No tienes permisos para ver hitos');

        $hito->load([
            'responsable:id,name,email',
            'entregables' => function ($query) {
                $query->with(['responsable:id,name,email', 'usuarios:id,name,email'])
                      ->orderBy('orden');
            }
        ]);

        // Calcular estadísticas del hito
        $estadisticas = [
            'total_entregables' => $hito->entregables->count(),
            'entregables_completados' => $hito->entregables->where('estado', 'completado')->count(),
            'entregables_pendientes' => $hito->entregables->where('estado', 'pendiente')->count(),
            'entregables_en_progreso' => $hito->entregables->where('estado', 'en_progreso')->count(),
            'porcentaje_completado' => $hito->porcentaje_completado,
            'dias_restantes' => $hito->dias_restantes,
        ];

        return Inertia::render('Modules/Proyectos/Admin/Hitos/Show', [
            'proyecto' => $proyecto,
            'hito' => $hito,
            'estadisticas' => $estadisticas,
            'canEdit' => auth()->user()->can('hitos.edit'),
            'canDelete' => auth()->user()->can('hitos.delete'),
            'canManageDeliverables' => auth()->user()->can('hitos.manage_deliverables'),
            'canCreateDeliverables' => auth()->user()->can('entregables.create'),
        ]);
    }

    /**
     * Muestra el formulario para editar un hito.
     */
    public function edit(Request $request, Proyecto $proyecto, Hito $hito): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('hitos.edit'), 403, 'No tienes permisos para editar hitos');

        $proyecto->load(['participantes.user:id,name,email']);
        $hito->load(['responsable:id,name,email']);

        $responsables = $proyecto->participantes()
            ->with('user:id,name,email')
            ->get()
            ->map(fn($p) => [
                'id' => $p->user->id,
                'name' => $p->user->name,
                'email' => $p->user->email
            ]);

        return Inertia::render('Modules/Proyectos/Admin/Hitos/Edit', [
            'proyecto' => $proyecto,
            'hito' => $hito,
            'responsables' => $responsables,
            'estados' => [
                ['value' => 'pendiente', 'label' => 'Pendiente'],
                ['value' => 'en_progreso', 'label' => 'En Progreso'],
                ['value' => 'completado', 'label' => 'Completado'],
                ['value' => 'cancelado', 'label' => 'Cancelado'],
            ]
        ]);
    }

    /**
     * Actualiza un hito en la base de datos.
     */
    public function update(UpdateHitoRequest $request, Proyecto $proyecto, Hito $hito): RedirectResponse
    {
        // El FormRequest ya verifica permisos con can('hitos.edit')

        $result = $this->hitoService->update($hito, $request->validated());

        if ($result['success']) {
            return redirect()
                ->route('admin.proyectos.hitos.show', [$proyecto, $hito])
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Elimina un hito de la base de datos.
     */
    public function destroy(Request $request, Proyecto $proyecto, Hito $hito): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('hitos.delete'), 403, 'No tienes permisos para eliminar hitos');

        $result = $this->hitoService->delete($hito);

        if ($result['success']) {
            return redirect()
                ->route('admin.proyectos.hitos.index', $proyecto)
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Duplica un hito con todos sus entregables.
     */
    public function duplicar(Request $request, Proyecto $proyecto, Hito $hito): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('hitos.create'), 403, 'No tienes permisos para duplicar hitos');

        $result = $this->hitoService->duplicar($hito);

        if ($result['success']) {
            return redirect()
                ->route('admin.proyectos.hitos.show', [$proyecto, $result['hito']])
                ->with('success', 'Hito duplicado exitosamente');
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Actualiza el orden de los hitos.
     */
    public function reordenar(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $this->authorize('edit', Hito::class);

        $request->validate([
            'hitos' => 'required|array',
            'hitos.*.id' => 'required|exists:hitos,id',
            'hitos.*.orden' => 'required|integer|min:0'
        ]);

        $result = $this->hitoRepository->reordenar($request->hitos);

        if ($result) {
            return redirect()
                ->back()
                ->with('success', 'Orden actualizado exitosamente');
        }

        return redirect()
            ->back()
            ->with('error', 'Error al actualizar el orden');
    }
}