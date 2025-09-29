<?php

namespace Modules\Proyectos\Http\Controllers\User;

use Modules\Core\Http\Controllers\UserController;
use Modules\Proyectos\Http\Requests\User\StoreEvidenciaRequest;
use Modules\Proyectos\Http\Requests\User\UpdateEvidenciaRequest;
use Modules\Proyectos\Services\EvidenciaService;
use Modules\Proyectos\Repositories\EvidenciaRepository;
use Modules\Proyectos\Models\Contrato;
use Modules\Proyectos\Models\Evidencia;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class EvidenciaController extends UserController
{
    public function __construct(
        private EvidenciaService $service,
        private EvidenciaRepository $repository
    ) {
        parent::__construct();
    }

    /**
     * Muestra el listado de evidencias del contrato.
     */
    public function index(Request $request, Contrato $contrato): Response
    {
        // Verificar que el usuario tenga acceso al contrato
        if (!$this->puedeVerContrato($contrato)) {
            abort(403, 'No tienes acceso a este contrato');
        }

        $evidencias = $this->repository->getByContratoPaginated($contrato->id, $request);

        return Inertia::render('Modules/Proyectos/User/Evidencias/Index', [
            'contrato' => $contrato->load(['proyecto', 'obligaciones']),
            'evidencias' => $evidencias,
            'filters' => $request->only(['search', 'tipo', 'estado', 'obligacion_id']),
            'estadisticas' => $this->repository->getEstadisticasPorContrato($contrato->id),
            'authPermissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray()
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva evidencia.
     */
    public function create(Contrato $contrato): Response
    {
        // Verificar que el usuario tenga acceso al contrato
        if (!$this->puedeVerContrato($contrato)) {
            abort(403, 'No tienes acceso a este contrato');
        }

        // Cargar obligaciones y entregables del proyecto
        $contrato->load([
            'obligaciones' => function ($query) {
                $query->orderBy('orden');
            },
            'proyecto.hitos.entregables' => function ($query) {
                $query->whereIn('estado', ['pendiente', 'en_progreso'])
                      ->orderBy('fecha_fin');
            }
        ]);

        return Inertia::render('Modules/Proyectos/User/Evidencias/Create', [
            'contrato' => $contrato,
            'obligaciones' => $contrato->obligaciones,
            'entregables' => $this->getEntregablesDelProyecto($contrato->proyecto),
            'tiposEvidencia' => [
                ['value' => 'imagen', 'label' => 'Imagen'],
                ['value' => 'video', 'label' => 'Video'],
                ['value' => 'audio', 'label' => 'Audio'],
                ['value' => 'documento', 'label' => 'Documento']
            ]
        ]);
    }

    /**
     * Guarda una nueva evidencia.
     */
    public function store(StoreEvidenciaRequest $request, Contrato $contrato): RedirectResponse
    {
        // Verificar que el usuario tenga acceso al contrato
        if (!$this->puedeVerContrato($contrato)) {
            abort(403, 'No tienes acceso a este contrato');
        }

        $result = $this->service->create($request->validated(), $contrato);

        if ($result['success']) {
            return redirect()
                ->route('user.mis-contratos.evidencias.index', $contrato)
                ->with('success', $result['message']);
        }

        return back()
            ->withErrors(['error' => $result['message']])
            ->withInput();
    }

    /**
     * Muestra una evidencia específica.
     */
    public function show(Contrato $contrato, Evidencia $evidencia): Response
    {
        // Verificar acceso
        if (!$this->puedeVerContrato($contrato) || $evidencia->obligacion->contrato_id !== $contrato->id) {
            abort(403, 'No tienes acceso a esta evidencia');
        }

        $evidencia->load(['usuario', 'obligacion', 'entregables', 'revisor']);

        return Inertia::render('Modules/Proyectos/User/Evidencias/Show', [
            'contrato' => $contrato,
            'evidencia' => $evidencia
        ]);
    }

    /**
     * Muestra el formulario para editar una evidencia.
     */
    public function edit(Contrato $contrato, Evidencia $evidencia): Response
    {
        // Verificar acceso y que sea editable
        if (!$evidencia->puedeSerEditadaPor(auth()->user())) {
            abort(403, 'No puedes editar esta evidencia');
        }

        // Cargar datos necesarios
        $contrato->load(['obligaciones', 'proyecto.hitos.entregables']);

        return Inertia::render('Modules/Proyectos/User/Evidencias/Edit', [
            'contrato' => $contrato,
            'evidencia' => $evidencia->load('entregables'),
            'obligaciones' => $contrato->obligaciones,
            'entregables' => $this->getEntregablesDelProyecto($contrato->proyecto),
            'tiposEvidencia' => [
                ['value' => 'imagen', 'label' => 'Imagen'],
                ['value' => 'video', 'label' => 'Video'],
                ['value' => 'audio', 'label' => 'Audio'],
                ['value' => 'documento', 'label' => 'Documento']
            ]
        ]);
    }

    /**
     * Actualiza una evidencia existente.
     */
    public function update(UpdateEvidenciaRequest $request, Contrato $contrato, Evidencia $evidencia): RedirectResponse
    {
        // Verificar acceso
        if (!$evidencia->puedeSerEditadaPor(auth()->user())) {
            abort(403, 'No puedes editar esta evidencia');
        }

        $result = $this->service->update($evidencia, $request->validated());

        if ($result['success']) {
            return redirect()
                ->route('user.mis-contratos.evidencias.show', [$contrato, $evidencia])
                ->with('success', $result['message']);
        }

        return back()
            ->withErrors(['error' => $result['message']])
            ->withInput();
    }

    /**
     * Elimina una evidencia.
     */
    public function destroy(Contrato $contrato, Evidencia $evidencia): RedirectResponse
    {
        // Verificar acceso
        if (!$evidencia->puedeSerEliminadaPor(auth()->user())) {
            abort(403, 'No puedes eliminar esta evidencia');
        }

        $result = $this->service->delete($evidencia);

        if ($result['success']) {
            return redirect()
                ->route('user.mis-contratos.evidencias.index', $contrato)
                ->with('success', $result['message']);
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    /**
     * Autoguardado de evidencia (borrador).
     */
    public function autosave(Request $request, Contrato $contrato): JsonResponse
    {
        // Verificar acceso al contrato
        if (!$this->puedeVerContrato($contrato)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este contrato'
            ], 403);
        }

        // Validación básica para autoguardado - datos vienen directamente del frontend
        $validated = $request->validate([
            'obligacion_id' => 'nullable|exists:obligaciones_contrato,id',
            'tipo_evidencia' => 'nullable|in:imagen,video,audio,documento',
            'descripcion' => 'nullable|string|max:1000',
            'entregable_ids' => 'nullable|array',
            'entregable_ids.*' => 'exists:entregables,id',
            'archivo' => 'nullable',
            'archivo_path' => 'nullable|string'
        ]);

        // Guardar en sesión o caché temporal
        $cacheKey = "evidencia_draft_{$contrato->id}_" . auth()->id();
        cache()->put($cacheKey, $validated, now()->addHours(24));

        return response()->json([
            'success' => true,
            'message' => 'Borrador guardado',
            'timestamp' => now()->toTimeString()
        ]);
    }

    /**
     * Verifica si el usuario puede ver el contrato.
     */
    private function puedeVerContrato(Contrato $contrato): bool
    {
        $userId = auth()->id();

        // Es el responsable del contrato
        if ($contrato->responsable_id === $userId) {
            return true;
        }

        // Es la contraparte
        if ($contrato->contraparte_user_id === $userId) {
            return true;
        }

        // Es participante del contrato
        if ($contrato->participantes()->where('user_id', $userId)->exists()) {
            return true;
        }

        // Es responsable del proyecto
        if ($contrato->proyecto->responsable_id === $userId) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene los entregables del proyecto formateados.
     */
    private function getEntregablesDelProyecto($proyecto): array
    {
        $entregables = [];

        foreach ($proyecto->hitos as $hito) {
            foreach ($hito->entregables as $entregable) {
                $entregables[] = [
                    'id' => $entregable->id,
                    'nombre' => $entregable->nombre,
                    'hito' => $hito->nombre,
                    'estado' => $entregable->estado,
                    'fecha_fin' => $entregable->fecha_fin?->format('Y-m-d')
                ];
            }
        }

        return $entregables;
    }
}