<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\AdminController;
use Modules\Core\Traits\HasAdvancedFilters;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Models\Entregable;
use Modules\Proyectos\Services\EntregableService;
use Modules\Proyectos\Repositories\EntregableRepository;
use Modules\Proyectos\Repositories\CampoPersonalizadoRepository;
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
        private EntregableRepository $entregableRepository,
        private CampoPersonalizadoRepository $campoPersonalizadoRepository
    ) {}

    /**
     * Muestra el formulario para crear un nuevo entregable.
     */
    public function create(Request $request, Proyecto $proyecto, Hito $hito): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.create'), 403, 'No tienes permisos para crear entregables');

        // Cargar participantes (BelongsToMany directo a User)
        $usuarios = $proyecto->participantes()
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);

        // Obtener campos personalizados para entregables
        $camposPersonalizados = $this->campoPersonalizadoRepository->getActivosParaEntregables();

        // Obtener categorías de etiquetas disponibles (filtradas para entregables)
        $categorias = \Modules\Proyectos\Models\CategoriaEtiqueta::with('etiquetas')
            ->activas()
            ->paraEntregables()
            ->ordenado()
            ->get();

        return Inertia::render('Modules/Proyectos/Admin/Entregables/Create', [
            'proyecto' => $proyecto,
            'hito' => $hito,
            'usuarios' => $usuarios,
            'camposPersonalizados' => $camposPersonalizados,
            'categorias' => $categorias,
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

        // El Service se encarga de campos personalizados y validación
        $result = $this->entregableService->create($data);

        if ($result['success']) {
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
     * Actualiza un entregable en la base de datos.
     */
    public function update(UpdateEntregableRequest $request, Proyecto $proyecto, Hito $hito, Entregable $entregable): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.edit'), 403, 'No tienes permisos para editar entregables');

        $data = $request->validated();

        // El Service se encarga de campos personalizados y validación
        $result = $this->entregableService->update($entregable, $data);

        if ($result['success']) {
            // Actualizar porcentaje del hito
            $hito->calcularPorcentajeCompletado();

            // Redirigir a la página del proyecto con el hito seleccionado
            return redirect("/admin/proyectos/{$proyecto->id}?tab=hitos&hito={$hito->id}")
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
     * Actualiza el estado de un entregable con observaciones.
     * Usa el método genérico cambiarEstado que registra en audit log.
     * Opcionalmente crea un comentario con contexto de cambio de estado.
     */
    public function actualizarEstado(Request $request, Proyecto $proyecto, Hito $hito, Entregable $entregable): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.edit'), 403, 'No tienes permisos para actualizar el estado de entregables');

        $request->validate([
            'estado' => 'required|in:pendiente,en_progreso,completado,cancelado',
            'observaciones' => 'nullable|string|max:1000',
            'agregar_comentario' => 'boolean',
            'archivos' => 'nullable|array|max:3',
            'archivos.*.path' => 'required_with:archivos|string',
            'archivos.*.name' => 'required_with:archivos|string',
            'archivos.*.mime_type' => 'nullable|string',
        ]);

        // Capturar estado anterior antes de cambiar
        $estadoAnterior = $entregable->estado;
        $labelAnterior = $entregable->estado_label;
        $colorAnterior = $entregable->estado_color;

        // Usar el método genérico que registra en audit log
        $entregable->cambiarEstado(
            $request->estado,
            auth()->id(),
            $request->observaciones
        );

        // Agregar comentario con contexto si se solicita
        if ($request->boolean('agregar_comentario') && $request->filled('observaciones')) {
            $entregable->agregarComentarioConContexto([
                'contenido' => $request->input('observaciones'),
                'tipo' => 'cambio_estado',
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $request->estado,
                'label_anterior' => $labelAnterior,
                'label_nuevo' => $entregable->estado_label,
                'color_anterior' => $colorAnterior,
                'color_nuevo' => $entregable->estado_color,
                'archivos' => $request->input('archivos', []),
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Estado del entregable actualizado');
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
            // Redirigir a la página del proyecto con el hito seleccionado
            return redirect("/admin/proyectos/{$proyecto->id}?tab=hitos&hito={$hito->id}")
                ->with('success', 'Entregable duplicado exitosamente');
        }

        return redirect()
            ->back()
            ->with('error', 'Error al duplicar el entregable');
    }
}