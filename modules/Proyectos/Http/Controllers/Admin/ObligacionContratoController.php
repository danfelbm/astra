<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\AdminController;
use Modules\Core\Models\User;
use Modules\Proyectos\Models\ObligacionContrato;
use Modules\Proyectos\Models\Contrato;
use Modules\Proyectos\Services\ObligacionContratoService;
use Modules\Proyectos\Repositories\ObligacionContratoRepository;
use Modules\Proyectos\Http\Requests\Admin\StoreObligacionContratoRequest;
use Modules\Proyectos\Http\Requests\Admin\UpdateObligacionContratoRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Response;
use Inertia\Inertia;

class ObligacionContratoController extends AdminController
{
    public function __construct(
        private ObligacionContratoService $service,
        private ObligacionContratoRepository $repository
    ) {}

    /**
     * Muestra el listado de obligaciones.
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('obligaciones.view'), 403, 'No tienes permisos para ver obligaciones');

        $contrato = null;
        if ($request->contrato_id) {
            $contrato = Contrato::with('proyecto')->find($request->contrato_id);
        }

        return Inertia::render('Modules/Proyectos/Admin/Obligaciones/Index', [
            'obligaciones' => $this->repository->getAllPaginated($request),
            'filters' => $request->only(['search', 'contrato_id']),
            'contrato' => $contrato,
            // Estadísticas eliminadas - campos deprecados
            'canCreate' => auth()->user()->can('obligaciones.create'),
            'canEdit' => auth()->user()->can('obligaciones.edit'),
            'canDelete' => auth()->user()->can('obligaciones.delete'),
            'canExport' => auth()->user()->can('obligaciones.export'),
        ]);
    }

    /**
     * Muestra el árbol de obligaciones para un contrato.
     */
    public function arbol(Request $request, int $contratoId): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('obligaciones.view'), 403, 'No tienes permisos para ver obligaciones');

        $contrato = Contrato::with('proyecto')->findOrFail($contratoId);
        $obligaciones = $this->repository->getArbolPorContrato($contratoId);

        return Inertia::render('Modules/Proyectos/Admin/Obligaciones/Arbol', [
            'contrato' => $contrato,
            'obligaciones' => $obligaciones,
            // Estadísticas eliminadas - campos deprecados
            'canCreate' => auth()->user()->can('obligaciones.create'),
            'canEdit' => auth()->user()->can('obligaciones.edit'),
            'canDelete' => auth()->user()->can('obligaciones.delete'),
        ]);
    }

    /**
     * Muestra el formulario para crear una obligación.
     */
    public function create(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('obligaciones.create'), 403, 'No tienes permisos para crear obligaciones');

        $contratoId = $request->contrato_id;
        $parentId = $request->parent_id;
        $contrato = null;
        $parent = null;

        if ($contratoId) {
            $contrato = Contrato::with('proyecto')->findOrFail($contratoId);
        }

        if ($parentId) {
            $parent = ObligacionContrato::findOrFail($parentId);
            // Si hay padre, usar su contrato
            $contrato = $parent->contrato;
        }

        $usuarios = User::select('id', 'name', 'email')->orderBy('name')->get();
        $contratos = Contrato::activos()->with('proyecto')->orderBy('nombre')->get();

        // Si hay contrato, obtener posibles padres
        $posiblesPadres = [];
        if ($contrato) {
            $posiblesPadres = ObligacionContrato::where('contrato_id', $contrato->id)
                                                ->select('id', 'titulo', 'nivel', 'parent_id')
                                                ->orderBy('orden')
                                                ->get();
        }

        return Inertia::render('Modules/Proyectos/Admin/Obligaciones/Create', [
            'contrato' => $contrato,
            'contratos' => $contratos,
            'parent' => $parent,
            'posiblesPadres' => $posiblesPadres,
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Almacena una nueva obligación.
     */
    public function store(StoreObligacionContratoRequest $request): RedirectResponse
    {
        $resultado = $this->service->create($request->validated());

        if (!$resultado['success']) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $resultado['message']]);
        }

        // Redirigir según el contexto
        if ($request->vista === 'arbol') {
            return redirect()
                ->route('admin.contratos.obligaciones.arbol', $resultado['obligacion']->contrato_id)
                ->with('success', $resultado['message']);
        }

        return redirect()
            ->route('admin.obligaciones.show', $resultado['obligacion'])
            ->with('success', $resultado['message']);
    }

    /**
     * Muestra una obligación específica.
     */
    public function show(ObligacionContrato $obligacion): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('obligaciones.view'), 403, 'No tienes permisos para ver esta obligación');

        $obligacion = $this->repository->findWithRelations($obligacion->id);

        // Cargar evidencias con sus relaciones
        $obligacion->load([
            'evidencias' => function ($query) {
                $query->with([
                    'usuario:id,name,email',
                    'entregables'
                ]);
            }
        ]);

        // Obtener actividades de la obligación
        $actividades = $obligacion->getActivityLogs(50);

        // Obtener usuarios únicos de las actividades para los filtros
        $usuariosActividades = $actividades
            ->pluck('causer')
            ->filter()
            ->unique('id')
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ])
            ->values();

        return Inertia::render('Modules/Proyectos/Admin/Obligaciones/Show', [
            'obligacion' => $obligacion,
            'contrato' => $obligacion->contrato->load('proyecto'),
            'actividades' => $actividades,
            'usuariosActividades' => $usuariosActividades,
            'canEdit' => auth()->user()->can('obligaciones.edit'),
            'canDelete' => auth()->user()->can('obligaciones.delete'),
            // canComplete eliminado - estado deprecado
            'canCreateChild' => auth()->user()->can('obligaciones.create'),
        ]);
    }

    /**
     * Muestra el formulario para editar una obligación.
     */
    public function edit(ObligacionContrato $obligacion): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('obligaciones.edit'), 403, 'No tienes permisos para editar esta obligación');

        $usuarios = User::select('id', 'name', 'email')->orderBy('name')->get();

        // Obtener posibles padres (excluyendo la obligación actual y sus descendientes)
        $posiblesPadres = ObligacionContrato::where('contrato_id', $obligacion->contrato_id)
                                            ->where('id', '!=', $obligacion->id)
                                            ->whereRaw("(path IS NULL OR path NOT LIKE ?)", ["%{$obligacion->id}%"])
                                            ->select('id', 'titulo', 'nivel', 'parent_id')
                                            ->orderBy('orden')
                                            ->get();

        return Inertia::render('Modules/Proyectos/Admin/Obligaciones/Edit', [
            'obligacion' => $obligacion->load(['padre', 'hijos']),
            'contrato' => $obligacion->contrato->load('proyecto'),
            'posiblesPadres' => $posiblesPadres,
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Actualiza una obligación.
     */
    public function update(UpdateObligacionContratoRequest $request, ObligacionContrato $obligacion): RedirectResponse
    {
        $resultado = $this->service->update($obligacion, $request->validated());

        if (!$resultado['success']) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $resultado['message']]);
        }

        return redirect()
            ->route('admin.obligaciones.show', $obligacion)
            ->with('success', $resultado['message']);
    }

    /**
     * Elimina una obligación.
     */
    public function destroy(ObligacionContrato $obligacion): RedirectResponse
    {
        $contratoId = $obligacion->contrato_id;
        $resultado = $this->service->delete($obligacion);

        if (!$resultado['success']) {
            if ($resultado['requiere_confirmacion'] ?? false) {
                return redirect()->back()
                    ->with('confirmar_eliminacion', [
                        'mensaje' => $resultado['message'],
                        'total_hijos' => $resultado['total_hijos']
                    ]);
            }

            return redirect()->back()
                ->withErrors(['error' => $resultado['message']]);
        }

        return redirect()
            ->route('admin.contratos.obligaciones.arbol', $contratoId)
            ->with('success', $resultado['message']);
    }

    // Método completar eliminado - campo estado deprecado

    /**
     * Duplica una obligación.
     */
    public function duplicar(Request $request, ObligacionContrato $obligacion): JsonResponse
    {
        $resultado = $this->service->duplicar(
            $obligacion,
            $request->input('nuevo_contrato_id')
        );

        if (!$resultado['success']) {
            return response()->json($resultado, 422);
        }

        return response()->json($resultado);
    }

    /**
     * Reordena las obligaciones.
     */
    public function reordenar(Request $request): JsonResponse
    {
        $request->validate([
            'orden_ids' => 'required|array',
            'orden_ids.*' => 'integer|exists:obligaciones_contrato,id',
            'contrato_id' => 'required|integer|exists:contratos,id',
            'parent_id' => 'nullable|integer|exists:obligaciones_contrato,id',
        ]);

        $resultado = $this->service->reordenar(
            $request->orden_ids,
            $request->contrato_id,
            $request->parent_id
        );

        if (!$resultado['success']) {
            return response()->json($resultado, 422);
        }

        return response()->json($resultado);
    }

    /**
     * Mueve una obligación a otro padre o posición.
     */
    public function mover(Request $request, ObligacionContrato $obligacion): JsonResponse
    {
        $request->validate([
            'nuevo_parent_id' => 'nullable|integer|exists:obligaciones_contrato,id',
            'nuevo_orden' => 'required|integer|min:1',
        ]);

        $resultado = $this->service->mover(
            $obligacion,
            $request->nuevo_parent_id,
            $request->nuevo_orden
        );

        if (!$resultado['success']) {
            return response()->json($resultado, 422);
        }

        return response()->json($resultado);
    }

    /**
     * Actualiza el estado de múltiples obligaciones.
     */
    public function actualizarEstadoMasivo(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:obligaciones_contrato,id',
            'estado' => 'required|in:pendiente,en_progreso,cumplida,vencida,cancelada',
        ]);

        $resultado = $this->service->actualizarEstadoMasivo(
            $request->ids,
            $request->estado
        );

        if (!$resultado['success']) {
            return response()->json($resultado, 422);
        }

        return response()->json($resultado);
    }

    /**
     * Busca obligaciones para autocompletar.
     */
    public function buscar(Request $request): JsonResponse
    {
        $request->validate([
            'termino' => 'required|string|min:2',
            'contrato_id' => 'nullable|integer|exists:contratos,id',
        ]);

        $obligaciones = $this->repository->buscarParaAutocompletar(
            $request->termino,
            $request->contrato_id
        );

        return response()->json($obligaciones);
    }

    /**
     * Obtiene la timeline de obligaciones.
     */
    public function timeline(int $contratoId): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('obligaciones.view'), 403, 'No tienes permisos para ver obligaciones');

        $contrato = Contrato::with('proyecto')->findOrFail($contratoId);
        $timeline = $this->repository->getTimelinePorContrato($contratoId);

        return Inertia::render('Modules/Proyectos/Admin/Obligaciones/Timeline', [
            'contrato' => $contrato,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Exporta las obligaciones a Excel.
     */
    public function exportar(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('obligaciones.export'), 403, 'No tienes permisos para exportar obligaciones');

        // TODO: Implementar exportación a Excel
        // Por ahora, retornar un mensaje
        return redirect()->back()->with('info', 'La funcionalidad de exportación se implementará próximamente');
    }
}