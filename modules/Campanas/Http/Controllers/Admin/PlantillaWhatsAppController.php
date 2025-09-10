<?php

namespace Modules\Campanas\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Campanas\Http\Requests\Admin\StorePlantillaWhatsAppRequest;
use Modules\Campanas\Http\Requests\Admin\UpdatePlantillaWhatsAppRequest;
use Modules\Campanas\Models\PlantillaWhatsApp;
use Modules\Campanas\Repositories\PlantillaRepository;
use Modules\Campanas\Services\PlantillaService;
use Modules\Core\Traits\HasAdvancedFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlantillaWhatsAppController extends AdminController
{
    use HasAdvancedFilters;

    public function __construct(
        private PlantillaService $service,
        private PlantillaRepository $repository
    ) {}

    /**
     * Mostrar listado de plantillas de WhatsApp
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.view'), 403, 'No tienes permisos para ver plantillas');

        $plantillas = $this->repository->getWhatsAppsPaginated($request, 15);

        return Inertia::render('Modules/Campanas/Admin/PlantillasWhatsApp/Index', [
            'plantillas' => $plantillas,
            'filters' => $request->only(['search', 'es_activa', 'advanced_filters']),
            'filterFieldsConfig' => $this->repository->getWhatsAppFilterFieldsConfig(),
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

        return Inertia::render('Modules/Campanas/Admin/PlantillasWhatsApp/Form', [
            'plantilla' => null,
            'variablesDisponibles' => config('campanas.template_variables'),
            'formatOptions' => [
                'bold' => '*texto*',
                'italic' => '_texto_',
                'strikethrough' => '~texto~',
                'monospace' => '```texto```',
            ],
        ]);
    }

    /**
     * Guardar nueva plantilla
     */
    public function store(StorePlantillaWhatsAppRequest $request): RedirectResponse
    {
        $result = $this->service->createWhatsApp($request->getValidatedData());

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return redirect()
            ->route('admin.campanas.plantillas-whatsapp.index')
            ->with('success', $result['message']);
    }

    /**
     * Mostrar plantilla específica
     */
    public function show(PlantillaWhatsApp $plantillaWhatsApp): RedirectResponse
    {
        // Redirigir al formulario de edición ya que no existe página Show
        return redirect()->route('admin.campanas.plantillas-whatsapp.edit', $plantillaWhatsApp);
    }

    /**
     * Mostrar formulario para editar plantilla
     */
    public function edit(PlantillaWhatsApp $plantillaWhatsApp): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.edit'), 403, 'No tienes permisos para editar plantillas');

        return Inertia::render('Modules/Campanas/Admin/PlantillasWhatsApp/Form', [
            'plantilla' => $plantillaWhatsApp,
            'variablesDisponibles' => config('campanas.template_variables'),
            'formatOptions' => [
                'bold' => '*texto*',
                'italic' => '_texto_',
                'strikethrough' => '~texto~',
                'monospace' => '```texto```',
            ],
        ]);
    }

    /**
     * Actualizar plantilla
     */
    public function update(UpdatePlantillaWhatsAppRequest $request, PlantillaWhatsApp $plantillaWhatsApp): RedirectResponse
    {
        $result = $this->service->updateWhatsApp($plantillaWhatsApp, $request->getValidatedData());

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return redirect()
            ->route('admin.campanas.plantillas-whatsapp.index')
            ->with('success', $result['message']);
    }

    /**
     * Eliminar plantilla
     */
    public function destroy(PlantillaWhatsApp $plantillaWhatsApp): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.delete'), 403, 'No tienes permisos para eliminar plantillas');

        $result = $this->service->deleteWhatsApp($plantillaWhatsApp);

        if (!$result['success']) {
            return redirect()
                ->route('admin.campanas.plantillas-whatsapp.index')
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.campanas.plantillas-whatsapp.index')
            ->with('success', $result['message']);
    }

    /**
     * Duplicar plantilla
     */
    public function duplicate(PlantillaWhatsApp $plantillaWhatsApp): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.create'), 403, 'No tienes permisos para duplicar plantillas');

        $result = $this->service->duplicateWhatsApp($plantillaWhatsApp);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.campanas.plantillas-whatsapp.edit', $result['plantilla'])
            ->with('success', $result['message']);
    }

    /**
     * Previsualizar plantilla con datos de usuario
     */
    public function preview(Request $request, PlantillaWhatsApp $plantillaWhatsApp): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.view'), 403, 'No tienes permisos para previsualizar plantillas');

        $result = $this->service->previewWhatsApp($plantillaWhatsApp, $request->input('user_id'));

        return response()->json($result);
    }

    /**
     * Validar contenido de plantilla
     */
    public function validateTemplate(Request $request): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.plantillas.view'), 403, 'No tienes permisos para validar plantillas');

        $result = $this->service->validateWhatsApp($request->all());

        return response()->json($result);
    }

    /**
     * Obtener plantillas activas para selector
     */
    public function getActive(): JsonResponse
    {
        $plantillas = $this->repository->getActiveWhatsApps();

        return response()->json([
            'success' => true,
            'plantillas' => $plantillas->map(function ($plantilla) {
                return [
                    'id' => $plantilla->id,
                    'nombre' => $plantilla->nombre,
                    'contenido' => $plantilla->contenido,
                    'descripcion' => $plantilla->descripcion,
                    'variables_usadas' => $plantilla->variables_usadas,
                    'usa_formato' => $plantilla->usa_formato,
                ];
            }),
        ]);
    }
}