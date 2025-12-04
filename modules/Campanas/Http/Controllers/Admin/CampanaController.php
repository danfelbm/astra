<?php

namespace Modules\Campanas\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Campanas\Http\Requests\Admin\StoreCampanaRequest;
use Modules\Campanas\Http\Requests\Admin\UpdateCampanaRequest;
use Modules\Campanas\Models\Campana;
use Modules\Campanas\Models\WhatsAppGroup;
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
            'whatsappModesOptions' => Campana::WHATSAPP_MODES,
            'whatsappGrupos' => WhatsAppGroup::select('id', 'group_jid', 'nombre', 'descripcion', 'tipo', 'avatar_url', 'participantes_count')
                ->orderBy('nombre')
                ->get(),
            'batchSizeEmailDefault' => config('campanas.batch_size.email'),
            'batchSizeWhatsAppDefault' => config('campanas.batch_size.whatsapp'),
            'whatsAppDelayDefault' => [
                'min' => config('campanas.whatsapp.delay_min'),
                'max' => config('campanas.whatsapp.delay_max'),
            ],
            // Configuración de campos para AdvancedFilters (modo manual)
            'filterFieldsConfig' => $this->getUserFilterFieldsConfig(),
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
            ->route('admin.envio-campanas.show', $result['campana'])
            ->with('success', $result['message']);
    }

    /**
     * Mostrar detalles y métricas de campaña
     */
    public function show(Campana $campana): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.view'), 403, 'No tienes permisos para ver campañas');

        // Cargar relaciones incluyendo grupos de WhatsApp
        $campana->load(['segment', 'plantillaEmail', 'plantillaWhatsApp', 'createdBy', 'whatsappGroups']);

        // Obtener métricas actualizadas
        $metricasResponse = $this->metricsService->getMetricas($campana);
        $comparacionResponse = $this->metricsService->getComparacion($campana);
        $tendenciasResponse = $this->metricsService->getTrends($campana);

        // Extraer solo las métricas de la respuesta (no el wrapper con 'success')
        $metricas = $metricasResponse['metricas'] ?? [];
        $comparacion = $comparacionResponse['comparacion'] ?? null;
        $tendencias = $tendenciasResponse['tendencias'] ?? [];

        // Obtener envíos recientes para el log de actividad
        // Transformar para soportar usuarios, grupos de WhatsApp y envíos individuales
        $enviosRecientes = $campana->envios()
            ->with('user:id,name,email')
            ->latest()
            ->take(50)
            ->get()
            ->map(function ($envio) {
                $data = [
                    'id' => $envio->id,
                    'tipo' => $envio->tipo, // 'email', 'whatsapp', 'whatsapp_group'
                    'estado' => $envio->estado,
                    'destinatario' => $envio->destinatario,
                    'fecha_enviado' => $envio->fecha_enviado,
                    'fecha_abierto' => $envio->fecha_abierto,
                    'fecha_primer_click' => $envio->fecha_primer_click,
                    'clicks_count' => $envio->clicks_count,
                    'aperturas_count' => $envio->aperturas_count,
                    'metadata' => $envio->metadata,
                    'error' => $envio->error_mensaje,
                    'created_at' => $envio->created_at,
                ];

                // Usuario individual (email o whatsapp individual)
                if ($envio->user) {
                    $data['user'] = [
                        'id' => $envio->user->id,
                        'nombre' => $envio->user->name,
                        'email' => $envio->user->email,
                    ];
                } elseif ($envio->tipo === 'whatsapp_group') {
                    // Para grupos de WhatsApp, usar metadata
                    $data['grupo'] = [
                        'nombre' => $envio->metadata['group_nombre'] ?? 'Grupo',
                        'participantes' => $envio->metadata['group_participantes'] ?? 0,
                        'jid' => $envio->destinatario,
                    ];
                }

                return $data;
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
                ->route('admin.envio-campanas.show', $campana)
                ->with('error', 'No se puede editar una campaña en estado: ' . $campana->estado);
        }

        // Cargar campaña con relaciones incluyendo grupos de WhatsApp
        $campana->load(['segment', 'plantillaEmail', 'plantillaWhatsApp', 'whatsappGroups']);

        return Inertia::render('Modules/Campanas/Admin/Campanas/Create', [
            'campana' => $campana,
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
            'whatsappModesOptions' => Campana::WHATSAPP_MODES,
            'whatsappGrupos' => WhatsAppGroup::select('id', 'group_jid', 'nombre', 'descripcion', 'tipo', 'avatar_url', 'participantes_count')
                ->orderBy('nombre')
                ->get(),
            'estadosOptions' => ['borrador', 'programada'], // Solo estos estados permitidos en edición
            // Configuración de campos para AdvancedFilters (modo manual)
            'filterFieldsConfig' => $this->getUserFilterFieldsConfig(),
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
            ->route('admin.envio-campanas.show', $campana)
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
                ->route('admin.envio-campanas.index')
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.envio-campanas.index')
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
            ->route('admin.envio-campanas.show', $campana)
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
            ->route('admin.envio-campanas.show', $campana)
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
            ->route('admin.envio-campanas.show', $campana)
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
            ->route('admin.envio-campanas.show', $campana)
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
            ->route('admin.envio-campanas.edit', $result['campana'])
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
     * Obtener logs de envío de la campaña
     * Distingue entre Resend (email), Evolution Individual y Evolution Grupos
     */
    public function logs(Campana $campana): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.view'), 403, 'No tienes permisos para ver logs');

        $logs = $campana->envios()
            ->select([
                'id', 'tipo', 'estado', 'destinatario',
                'fecha_enviado', 'error_mensaje', 'metadata'
            ])
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get()
            ->map(function ($envio) {
                // Determinar servicio según tipo de envío
                $servicio = match ($envio->tipo) {
                    'email' => 'resend',
                    'whatsapp' => 'evolution_individual',
                    'whatsapp_group' => 'evolution_group',
                    default => 'unknown',
                };

                $data = [
                    'id' => $envio->id,
                    'tipo' => $envio->tipo,
                    'servicio' => $servicio,
                    'estado' => $envio->estado,
                    'destinatario' => $envio->destinatario,
                    'fecha' => $envio->fecha_enviado?->toIso8601String(),
                    'mensaje_id' => $envio->metadata['resend_id'] ?? $envio->metadata['whatsapp_message_id'] ?? null,
                    'error' => $envio->error_mensaje,
                    'metadata' => $envio->metadata,
                ];

                // Agregar info de grupo si es envío a grupo
                if ($envio->tipo === 'whatsapp_group') {
                    $data['grupo'] = [
                        'nombre' => $envio->metadata['group_nombre'] ?? null,
                        'participantes' => $envio->metadata['group_participantes'] ?? null,
                    ];
                }

                return $data;
            });

        return response()->json($logs);
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

    /**
     * Contar usuarios filtrados para modo manual de audiencia
     * Endpoint AJAX para previsualización en tiempo real
     */
    public function countFilteredUsers(Request $request): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('campanas.create'), 403, 'No tienes permisos');

        $filters = $request->input('filters');

        if (empty($filters)) {
            return response()->json(['count' => 0, 'valid' => false]);
        }

        $count = $this->service->countFilteredUsers($filters);

        return response()->json([
            'count' => $count,
            'valid' => $count > 0,
        ]);
    }

    /**
     * Obtener configuración de campos para AdvancedFilters
     * Usado en modo manual de audiencia
     */
    protected function getUserFilterFieldsConfig(): array
    {
        // Cargar opciones geográficas
        $territorios = \Modules\Geografico\Models\Territorio::select('id', 'nombre')
            ->orderBy('nombre')
            ->get()
            ->map(fn($t) => ['value' => (string) $t->id, 'label' => $t->nombre])
            ->toArray();

        $departamentos = \Modules\Geografico\Models\Departamento::select('id', 'nombre')
            ->orderBy('nombre')
            ->get()
            ->map(fn($d) => ['value' => (string) $d->id, 'label' => $d->nombre])
            ->toArray();

        $municipios = \Modules\Geografico\Models\Municipio::select('id', 'nombre')
            ->orderBy('nombre')
            ->get()
            ->map(fn($m) => ['value' => (string) $m->id, 'label' => $m->nombre])
            ->toArray();

        return [
            [
                'name' => 'name',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'text',
            ],
            [
                'name' => 'activo',
                'label' => 'Activo',
                'type' => 'select',
                'options' => [
                    ['value' => '1', 'label' => 'Sí'],
                    ['value' => '0', 'label' => 'No'],
                ],
            ],
            [
                'name' => 'created_at',
                'label' => 'Fecha de registro',
                'type' => 'date',
            ],
            [
                'name' => 'territorio_id',
                'label' => 'Territorio',
                'type' => 'select',
                'options' => $territorios,
            ],
            [
                'name' => 'departamento_id',
                'label' => 'Departamento',
                'type' => 'select',
                'options' => $departamentos,
            ],
            [
                'name' => 'municipio_id',
                'label' => 'Municipio',
                'type' => 'select',
                'options' => $municipios,
            ],
        ];
    }
}