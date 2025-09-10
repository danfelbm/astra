<?php

namespace Modules\Campanas\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Campanas\Http\Requests\Admin\StorePlantillaEmailRequest;
use Modules\Campanas\Http\Requests\Admin\UpdatePlantillaEmailRequest;
use Modules\Campanas\Models\PlantillaEmail;
use Modules\Campanas\Repositories\PlantillaRepository;
use Modules\Campanas\Services\PlantillaService;
use Modules\Core\Traits\HasAdvancedFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlantillaEmailController extends AdminController
{
    use HasAdvancedFilters;

    public function __construct(
        private PlantillaService $service,
        private PlantillaRepository $repository
    ) {}

    /**
     * Mostrar listado de plantillas de email
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.view'), 403, 'No tienes permisos para ver plantillas');

        $plantillas = $this->repository->getEmailsPaginated($request, 15);

        return Inertia::render('Modules/Campanas/Admin/PlantillasEmail/Index', [
            'plantillas' => $plantillas,
            'filters' => $request->only(['search', 'es_activa', 'advanced_filters']),
            'filterFieldsConfig' => $this->repository->getEmailFilterFieldsConfig(),
            'canCreate' => auth()->user()->can('campanas.plantillas.create'),
            'canEdit' => auth()->user()->can('campanas.plantillas.edit'),
            'canDelete' => auth()->user()->can('campanas.plantillas.delete'),
        ]);
    }

    /**
     * Mostrar formulario para crear plantilla
     */
    public function create(): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.create'), 403, 'No tienes permisos para crear plantillas');

        return Inertia::render('Modules/Campanas/Admin/PlantillasEmail/Form', [
            'plantilla' => null,
            'variablesDisponibles' => config('campanas.template_variables'),
        ]);
    }

    /**
     * Guardar nueva plantilla
     */
    public function store(StorePlantillaEmailRequest $request): RedirectResponse
    {
        $result = $this->service->createEmail($request->getValidatedData());

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return redirect()
            ->route('admin.campanas.plantillas-email.index')
            ->with('success', $result['message']);
    }

    /**
     * Mostrar plantilla específica
     */
    public function show(PlantillaEmail $plantillaEmail): RedirectResponse
    {
        // Redirigir al formulario de edición ya que no existe página Show
        return redirect()->route('admin.campanas.plantillas-email.edit', $plantillaEmail);
    }

    /**
     * Mostrar formulario para editar plantilla
     */
    public function edit(PlantillaEmail $plantillaEmail): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.edit'), 403, 'No tienes permisos para editar plantillas');

        return Inertia::render('Modules/Campanas/Admin/PlantillasEmail/Form', [
            'plantilla' => $plantillaEmail,
            'variablesDisponibles' => config('campanas.template_variables'),
        ]);
    }

    /**
     * Actualizar plantilla
     */
    public function update(UpdatePlantillaEmailRequest $request, PlantillaEmail $plantillaEmail): RedirectResponse
    {
        $result = $this->service->updateEmail($plantillaEmail, $request->getValidatedData());

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return redirect()
            ->route('admin.campanas.plantillas-email.index')
            ->with('success', $result['message']);
    }

    /**
     * Eliminar plantilla
     */
    public function destroy(PlantillaEmail $plantillaEmail): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.delete'), 403, 'No tienes permisos para eliminar plantillas');

        $result = $this->service->deleteEmail($plantillaEmail);

        if (!$result['success']) {
            return redirect()
                ->route('admin.campanas.plantillas-email.index')
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.campanas.plantillas-email.index')
            ->with('success', $result['message']);
    }

    /**
     * Duplicar plantilla
     */
    public function duplicate(PlantillaEmail $plantillaEmail): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.create'), 403, 'No tienes permisos para duplicar plantillas');

        $result = $this->service->duplicateEmail($plantillaEmail);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.campanas.plantillas-email.edit', $result['plantilla'])
            ->with('success', $result['message']);
    }

    /**
     * Previsualizar plantilla
     */
    public function preview(Request $request, PlantillaEmail $plantillaEmail): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.view'), 403, 'No tienes permisos para previsualizar plantillas');

        $result = $this->service->previewEmail($plantillaEmail, $request->input('user_id'));

        return response()->json($result);
    }

    /**
     * Validar plantilla
     */
    public function validateTemplate(Request $request): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.view'), 403, 'No tienes permisos para validar plantillas');

        $result = $this->service->validateEmail($request->all());

        return response()->json($result);
    }

    /**
     * Obtener plantillas activas para selector
     */
    public function getActive(): JsonResponse
    {
        $plantillas = $this->repository->getActiveEmails();

        return response()->json([
            'success' => true,
            'plantillas' => $plantillas->map(function ($plantilla) {
                return [
                    'id' => $plantilla->id,
                    'nombre' => $plantilla->nombre,
                    'asunto' => $plantilla->asunto,
                    'descripcion' => $plantilla->descripcion,
                    'variables_usadas' => $plantilla->variables_usadas,
                ];
            }),
        ]);
    }
}