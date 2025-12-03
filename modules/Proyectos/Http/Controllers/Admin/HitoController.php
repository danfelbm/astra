<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\AdminController;
use Modules\Core\Traits\HasAdvancedFilters;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Services\HitoService;
use Modules\Proyectos\Repositories\HitoRepository;
use Modules\Proyectos\Repositories\CampoPersonalizadoRepository;
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
        private HitoRepository $hitoRepository,
        private CampoPersonalizadoRepository $campoPersonalizadoRepository
    ) {}

    /**
     * Muestra el formulario para crear un nuevo hito.
     */
    public function create(Request $request, Proyecto $proyecto): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('hitos.create'), 403, 'No tienes permisos para crear hitos');

        $proyecto->load(['responsable']);

        // Obtener posibles responsables del proyecto (participantes es BelongsToMany directo a User)
        $responsables = $proyecto->participantes()
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);

        // Obtener hitos disponibles como padres
        $hitosDisponibles = $proyecto->hitos()
            ->with('parent:id,nombre')
            ->orderBy('orden')
            ->get()
            ->map(fn($h) => [
                'id' => $h->id,
                'nombre' => $h->nombre,
                'ruta_completa' => $h->ruta_completa,
                'nivel' => $h->nivel
            ]);

        // Obtener campos personalizados para hitos
        $camposPersonalizados = $this->campoPersonalizadoRepository->getActivosParaHitos();

        // Obtener categorías de etiquetas disponibles (filtradas para hitos)
        $categorias = \Modules\Proyectos\Models\CategoriaEtiqueta::with('etiquetas')
            ->activas()
            ->paraHitos()
            ->ordenado()
            ->get();

        return Inertia::render('Modules/Proyectos/Admin/Hitos/Create', [
            'proyecto' => $proyecto,
            'responsables' => $responsables,
            'hitosDisponibles' => $hitosDisponibles,
            'camposPersonalizados' => $camposPersonalizados,
            'categorias' => $categorias,
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

        // El Service se encarga de campos personalizados y toda la lógica de negocio
        $result = $this->hitoService->create($data);

        if ($result['success']) {
            // Redirigir a la página del proyecto con el tab de hitos
            return redirect("/admin/proyectos/{$proyecto->id}?tab=hitos")
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Actualiza un hito en la base de datos.
     */
    public function update(UpdateHitoRequest $request, Proyecto $proyecto, Hito $hito): RedirectResponse
    {
        // El FormRequest ya verifica permisos con can('hitos.edit')

        $data = $request->validated();

        // El Service se encarga de campos personalizados y jerarquía
        $result = $this->hitoService->update($hito, $data);

        if ($result['success']) {
            // Redirigir a la página del proyecto con el hito seleccionado
            return redirect("/admin/proyectos/{$proyecto->id}?tab=hitos&hito={$hito->id}")
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
            // Redirigir a la página del proyecto con el tab de hitos
            return redirect("/admin/proyectos/{$proyecto->id}?tab=hitos")
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
            // Redirigir a la página del proyecto con el nuevo hito seleccionado
            return redirect("/admin/proyectos/{$proyecto->id}?tab=hitos&hito={$result['hito']->id}")
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