<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Proyectos\Models\CampoPersonalizado;
use Modules\Proyectos\Http\Requests\Admin\StoreCampoPersonalizadoRequest;
use Modules\Proyectos\Http\Requests\Admin\UpdateCampoPersonalizadoRequest;
use Modules\Proyectos\Services\CampoPersonalizadoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class CampoPersonalizadoController extends AdminController
{
    public function __construct(
        private CampoPersonalizadoService $service
    ) {
        parent::__construct();
    }

    /**
     * Muestra la lista de campos personalizados.
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.manage_fields'), 403, 'No tienes permisos para ver campos personalizados');

        $campos = CampoPersonalizado::query()
            ->when($request->search, function ($query, $search) {
                $query->where('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
            })
            ->when($request->tipo, function ($query, $tipo) {
                $query->where('tipo', $tipo);
            })
            ->when($request->has('activo'), function ($query) use ($request) {
                $query->where('activo', $request->activo);
            })
            ->ordenado()
            ->paginate(config('proyectos.paginacion.campos_por_pagina'));

        return Inertia::render('Modules/Proyectos/Admin/CamposPersonalizados/Index', [
            'campos' => $campos,
            'filters' => $request->only(['search', 'tipo', 'activo']),
            'tiposCampo' => CampoPersonalizado::TIPOS_DISPONIBLES,
            'entidadesDisponibles' => [
                'proyectos' => 'Proyectos',
                'contratos' => 'Contratos'
            ],
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo campo.
     */
    public function create(): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.manage_fields'), 403, 'No tienes permisos para crear campos personalizados');

        return Inertia::render('Modules/Proyectos/Admin/CamposPersonalizados/Form', [
            'tiposCampo' => CampoPersonalizado::TIPOS_DISPONIBLES,
            'entidadesDisponibles' => [
                'proyectos' => 'Proyectos',
                'contratos' => 'Contratos'
            ],
        ]);
    }

    /**
     * Almacena un nuevo campo personalizado.
     */
    public function store(StoreCampoPersonalizadoRequest $request): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.manage_fields'), 403, 'No tienes permisos para crear campos personalizados');

        $this->service->create($request->validated());

        return redirect()
            ->route('admin.campos-personalizados.index')
            ->with('success', 'Campo personalizado creado exitosamente');
    }

    /**
     * Muestra los detalles de un campo personalizado.
     */
    public function show(CampoPersonalizado $campo): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.manage_fields'), 403, 'No tienes permisos para ver campos personalizados');

        $campo->load('valores.proyecto');

        return Inertia::render('Modules/Proyectos/Admin/CamposPersonalizados/Show', [
            'campo' => $campo,
            'proyectosConValores' => $campo->valores->count(),
        ]);
    }

    /**
     * Muestra el formulario para editar un campo.
     */
    public function edit(CampoPersonalizado $campo): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.manage_fields'), 403, 'No tienes permisos para editar campos personalizados');

        return Inertia::render('Modules/Proyectos/Admin/CamposPersonalizados/Form', [
            'campo' => $campo,
            'tiposCampo' => CampoPersonalizado::TIPOS_DISPONIBLES,
            'entidadesDisponibles' => [
                'proyectos' => 'Proyectos',
                'contratos' => 'Contratos'
            ],
        ]);
    }

    /**
     * Actualiza un campo personalizado existente.
     */
    public function update(UpdateCampoPersonalizadoRequest $request, CampoPersonalizado $campo): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.manage_fields'), 403, 'No tienes permisos para editar campos personalizados');

        $this->service->update($campo, $request->validated());

        return redirect()
            ->route('admin.campos-personalizados.index')
            ->with('success', 'Campo personalizado actualizado exitosamente');
    }

    /**
     * Elimina un campo personalizado.
     */
    public function destroy(CampoPersonalizado $campo): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.manage_fields'), 403, 'No tienes permisos para eliminar campos personalizados');

        // Verificar si tiene valores asociados
        if ($campo->valores()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'No se puede eliminar el campo porque tiene valores asociados a proyectos');
        }

        $campo->delete();

        return redirect()
            ->route('admin.campos-personalizados.index')
            ->with('success', 'Campo personalizado eliminado exitosamente');
    }

    /**
     * Cambia el estado activo de un campo personalizado.
     */
    public function toggleActivo(CampoPersonalizado $campo, Request $request): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.manage_fields'), 403, 'No tienes permisos para cambiar el estado de campos');

        $request->validate([
            'activo' => 'required|boolean',
        ]);

        $campo->update([
            'activo' => $request->activo
        ]);

        return redirect()
            ->back()
            ->with('success', 'Estado del campo actualizado exitosamente');
    }

    /**
     * Reordena los campos personalizados.
     */
    public function reordenar(Request $request): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('proyectos.manage_fields'), 403, 'No tienes permisos para reordenar campos');

        $request->validate([
            'campos' => 'required|array',
            'campos.*.id' => 'required|exists:campos_personalizados,id',
            'campos.*.orden' => 'required|integer|min:0',
        ]);

        foreach ($request->campos as $campo) {
            CampoPersonalizado::where('id', $campo['id'])
                ->update(['orden' => $campo['orden']]);
        }

        return redirect()
            ->back()
            ->with('success', 'Orden de campos actualizado exitosamente');
    }
}