<?php

namespace Modules\Campanas\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Campanas\Http\Requests\Admin\StoreCampanaRequest;
use Modules\Campanas\Http\Requests\Admin\UpdateCampanaRequest;
use Modules\Campanas\Models\Campana;
use Modules\Campanas\Repositories\CampanaRepository;
use Modules\Campanas\Repositories\PlantillaRepository;
use Modules\Campanas\Services\CampanaService;
use Modules\Campanas\Services\CampanaMetricsService;
use Modules\Core\Models\Segment;
use Modules\Core\Traits\HasAdvancedFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampanaController extends AdminController
{
    use HasAdvancedFilters;

    public function __construct(
        private CampanaService $service,
        private CampanaRepository $repository,
        private CampanaMetricsService $metricsService,
        private PlantillaRepository $plantillaRepository
    ) {}

    /**
     * Mostrar listado de campañas
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.view'), 403, 'No tienes permisos para ver campañas');

        $campanas = $this->repository->getAllPaginated($request, 15);

        return Inertia::render('Modules/Campanas/Admin/Campanas/Index', [
            'campanas' => $campanas,
            'filters' => $request->only(['search', 'estado', 'tipo', 'fecha_desde', 'fecha_hasta', 'advanced_filters']),
            'filterFieldsConfig' => $this->repository->getFilterFieldsConfig(),
            'estadosOptions' => Campana::ESTADOS,
            'tiposOptions' => Campana::TIPOS,
            'canCreate' => auth()->user()->can('campanas.create'),
            'canEdit' => auth()->user()->can('campanas.edit'),
            'canDelete' => auth()->user()->can('campanas.delete'),
            'canSend' => auth()->user()->can('campanas.send'),
        ]);
    }

    /**
     * Mostrar formulario para crear campaña
     */
    public function create(): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.create'), 403, 'No tienes permisos para crear campañas');

        return Inertia::render('Modules/Campanas/Admin/Campanas/Create', [
            'segmentos' => Segment::select('id', 'name', 'description', 'model_type', 'is_dynamic', 'filters', 'metadata', 'cache_duration', 'created_at', 'updated_at')
                ->orderBy('name')
                ->get()
                ->map(function ($segment) {
                    return [
                        'id' => $segment->id,
                        'nombre' => $segment->name,
                        'descripcion' => $segment->description ?: 'Segmento dinámico basado en filtros',
                        'tipo' => $segment->is_dynamic ? 'dinamico' : 'personalizado',
                        'count' => $segment->getCount(),
                        'created_at' => $segment->created_at,
                        'updated_at' => $segment->updated_at,
                        'filtros' => null, // Se puede agregar después si se necesita
                    ];
                }),
            'plantillasEmail' => $this->plantillaRepository->getActiveEmails(),
            'plantillasWhatsApp' => $this->plantillaRepository->getActiveWhatsApps(),
            'tiposOptions' => Campana::TIPOS,
            'batchSizeEmailDefault' => config('campanas.batch_size.email'),
            'batchSizeWhatsAppDefault' => config('campanas.batch_size.whatsapp'),
            'whatsAppDelayDefault' => [
                'min' => config('campanas.whatsapp.delay_min'),
                'max' => config('campanas.whatsapp.delay_max'),
            ],
        ]);
    }

    /**
     * Guardar nueva campaña
     */
    public function store(StoreCampanaRequest $request): RedirectResponse
    {
        $result = $this->service->create($request->getValidatedData());

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return redirect()
            ->route('admin.campanas.show', $result['campana'])
            ->with('success', $result['message']);
    }

    /**
     * Mostrar detalles y métricas de campaña
     */
    public function show(Campana $campana): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.view'), 403, 'No tienes permisos para ver campañas');

        $campana->load(['segment', 'plantillaEmail', 'plantillaWhatsApp', 'createdBy']);
        
        // Obtener métricas actualizadas
        $metricasResponse = $this->metricsService->getMetricas($campana);
        $comparacionResponse = $this->metricsService->getComparacion($campana);
        $tendenciasResponse = $this->metricsService->getTrends($campana);
        
        // Extraer solo las métricas de la respuesta (no el wrapper con 'success')
        $metricas = $metricasResponse['metricas'] ?? [];
        $comparacion = $comparacionResponse['comparacion'] ?? null;
        $tendencias = $tendenciasResponse['tendencias'] ?? [];
        
        // Obtener envíos recientes para el log de actividad
        $enviosRecientes = $campana->envios()
            ->with('user:id,name,email')
            ->latest()
            ->take(50)
            ->get();

        // Transformar 'name' a 'nombre' para consistencia con frontend
        $enviosRecientes = $enviosRecientes->map(function ($envio) {
            if ($envio->user) {
                $envio->user->nombre = $envio->user->name;
            }
            return $envio;
        });

        return Inertia::render('Modules/Campanas/Admin/Campanas/Show', [
            'campana' => $campana,
            'metricas' => $metricas,
            'comparacion' => $comparacion,
            'tendencias' => $tendencias,
            'enviosRecientes' => $enviosRecientes,
            'canEdit' => auth()->user()->can('campanas.edit'),
            'canDelete' => auth()->user()->can('campanas.delete'),
            'canPause' => auth()->user()->can('campanas.pause'),
            'canResume' => auth()->user()->can('campanas.resume'),
            'canCancel' => auth()->user()->can('campanas.cancel'),
        ]);
    }

    /**
     * Mostrar formulario para editar campaña
     */
    public function edit(Campana $campana): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.edit'), 403, 'No tienes permisos para editar campañas');

        // No permitir edición si está en proceso o completada
        if (in_array($campana->estado, ['enviando', 'completada', 'cancelada'])) {
            return redirect()
                ->route('admin.campanas.show', $campana)
                ->with('error', 'No se puede editar una campaña en estado: ' . $campana->estado);
        }

        return Inertia::render('Modules/Campanas/Admin/Campanas/Create', [
            'campana' => $campana->load(['segment', 'plantillaEmail', 'plantillaWhatsApp']),
            'segmentos' => Segment::select('id', 'name', 'description', 'model_type', 'is_dynamic', 'filters', 'metadata', 'cache_duration', 'created_at', 'updated_at')
                ->orderBy('name')
                ->get()
                ->map(function ($segment) {
                    return [
                        'id' => $segment->id,
                        'nombre' => $segment->name,
                        'descripcion' => $segment->description ?: 'Segmento dinámico basado en filtros',
                        'tipo' => $segment->is_dynamic ? 'dinamico' : 'personalizado',
                        'count' => $segment->getCount(),
                        'created_at' => $segment->created_at,
                        'updated_at' => $segment->updated_at,
                        'filtros' => null, // Se puede agregar después si se necesita
                    ];
                }),
            'plantillasEmail' => $this->plantillaRepository->getActiveEmails(),
            'plantillasWhatsApp' => $this->plantillaRepository->getActiveWhatsApps(),
            'tiposOptions' => Campana::TIPOS,
            'estadosOptions' => ['borrador', 'programada'], // Solo estos estados permitidos en edición
        ]);
    }

    /**
     * Actualizar campaña
     */
    public function update(UpdateCampanaRequest $request, Campana $campana): RedirectResponse
    {
        $result = $this->service->update($campana, $request->getValidatedData());

        if (!$result['success']) {
            return back()->withErrors(['error' => $result['message']]);
        }

        return redirect()
            ->route('admin.campanas.show', $campana)
            ->with('success', $result['message']);
    }

    /**
     * Eliminar campaña
     */
    public function destroy(Campana $campana): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.delete'), 403, 'No tienes permisos para eliminar campañas');

        $result = $this->service->delete($campana);

        if (!$result['success']) {
            return redirect()
                ->route('admin.campanas.index')
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.campanas.index')
            ->with('success', $result['message']);
    }

    /**
     * Iniciar envío de campaña
     */
    public function send(Campana $campana): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.send'), 403, 'No tienes permisos para enviar campañas');

        $result = $this->service->iniciarEnvio($campana);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.campanas.show', $campana)
            ->with('success', $result['message']);
    }

    /**
     * Pausar campaña en curso
     */
    public function pause(Campana $campana): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.pause'), 403, 'No tienes permisos para pausar campañas');

        $result = $this->service->pausar($campana);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.campanas.show', $campana)
            ->with('success', $result['message']);
    }

    /**
     * Reanudar campaña pausada
     */
    public function resume(Campana $campana): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.resume'), 403, 'No tienes permisos para reanudar campañas');

        $result = $this->service->reanudar($campana);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.campanas.show', $campana)
            ->with('success', $result['message']);
    }

    /**
     * Cancelar campaña
     */
    public function cancel(Campana $campana): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.cancel'), 403, 'No tienes permisos para cancelar campañas');

        $result = $this->service->cancelar($campana);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.campanas.show', $campana)
            ->with('success', $result['message']);
    }

    /**
     * Duplicar campaña para crear una nueva
     */
    public function duplicate(Campana $campana): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.create'), 403, 'No tienes permisos para duplicar campañas');

        $result = $this->service->duplicate($campana);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.campanas.edit', $result['campana'])
            ->with('success', $result['message']);
    }

    /**
     * Obtener métricas en tiempo real (para polling/websocket)
     */
    public function metrics(Campana $campana): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.view'), 403, 'No tienes permisos para ver métricas');

        $metricasResponse = $this->metricsService->getMetricas($campana);
        $progreso = $campana->getProgreso();

        return response()->json([
            'success' => true,
            'metricas' => $metricasResponse['metricas'] ?? [],  // Extraer solo las métricas
            'progreso' => $progreso,
            'estado' => $campana->estado,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Exportar reporte de campaña
     */
    public function export(Request $request, Campana $campana): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.export'), 403, 'No tienes permisos para exportar reportes');

        $formato = $request->input('formato', 'excel');
        $incluirDetalles = $request->boolean('incluir_detalles', false);

        $result = $this->service->exportarReporte($campana, $formato, $incluirDetalles);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Previsualizar campaña antes de envío
     */
    public function preview(Request $request, Campana $campana): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.view'), 403, 'No tienes permisos para previsualizar campañas');

        $userId = $request->input('user_id', auth()->id());
        $result = $this->service->preview($campana, $userId);

        return response()->json($result);
    }

    /**
     * Obtener estadísticas globales de campañas
     */
    public function stats(): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.view'), 403, 'No tienes permisos para ver estadísticas');

        $stats = $this->repository->getGlobalStats();

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}