<?php

namespace Modules\Proyectos\Http\Controllers\User;

use Modules\Core\Http\Controllers\UserController;
use Modules\Proyectos\Models\ObligacionContrato;
use Modules\Proyectos\Services\ObligacionContratoService;
use Modules\Proyectos\Repositories\ObligacionContratoRepository;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Response;
use Inertia\Inertia;

class MisObligacionesController extends UserController
{
    public function __construct(
        private ObligacionContratoService $service,
        private ObligacionContratoRepository $repository
    ) {
        parent::__construct();
    }

    /**
     * Muestra las obligaciones del usuario actual.
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('obligaciones.view_own'), 403, 'No tienes permisos para ver tus obligaciones');

        $obligaciones = $this->repository->getMisObligaciones($request);
        $estadisticas = $this->getEstadisticasUsuario();
        $obligacionesCriticas = $this->getObligacionesCriticasUsuario();

        return Inertia::render('Modules/Proyectos/User/MisObligaciones/Index', [
            'obligaciones' => $obligaciones,
            'filters' => $request->only(['search', 'estado', 'prioridad']),
            'estadisticas' => $estadisticas,
            'obligacionesCriticas' => $obligacionesCriticas,
            'canComplete' => auth()->user()->can('obligaciones.complete_own'),
        ]);
    }

    /**
     * Muestra el detalle de una obligación del usuario.
     */
    public function show(ObligacionContrato $obligacion): Response
    {
        // Verificar que la obligación pertenezca al usuario
        if ($obligacion->responsable_id !== auth()->id()) {
            abort(403, 'No tienes permisos para ver esta obligación');
        }

        $obligacion = $this->repository->findWithRelations($obligacion->id);

        return Inertia::render('Modules/Proyectos/User/MisObligaciones/Show', [
            'obligacion' => $obligacion,
            'contrato' => $obligacion->contrato->load('proyecto'),
            'canComplete' => $obligacion->puedeSerCompletadaPor(auth()->user()),
        ]);
    }

    /**
     * Marca una obligación como cumplida.
     */
    public function completar(Request $request, ObligacionContrato $obligacion): JsonResponse
    {
        // Verificar que el usuario puede completar esta obligación
        if (!$obligacion->puedeSerCompletadaPor(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para completar esta obligación'
            ], 403);
        }

        $resultado = $this->service->marcarComoCumplida(
            $obligacion,
            $request->input('notas_cumplimiento')
        );

        if (!$resultado['success']) {
            return response()->json($resultado, 422);
        }

        return response()->json($resultado);
    }

    /**
     * Actualiza el progreso de una obligación.
     */
    public function actualizarProgreso(Request $request, ObligacionContrato $obligacion): JsonResponse
    {
        // Verificar que la obligación pertenezca al usuario
        if ($obligacion->responsable_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para actualizar esta obligación'
            ], 403);
        }

        $request->validate([
            'porcentaje_cumplimiento' => 'required|integer|min:0|max:100',
            'notas' => 'nullable|string|max:1000',
        ]);

        // Solo actualizar progreso si no está cumplida
        if ($obligacion->estado === 'cumplida') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede actualizar el progreso de una obligación cumplida'
            ], 422);
        }

        $data = [
            'porcentaje_cumplimiento' => $request->porcentaje_cumplimiento,
        ];

        // Si el porcentaje es mayor a 0, marcar como en progreso
        if ($request->porcentaje_cumplimiento > 0 && $obligacion->estado === 'pendiente') {
            $data['estado'] = 'en_progreso';
        }

        // Si el porcentaje es 100, preguntar si desea marcarla como cumplida
        if ($request->porcentaje_cumplimiento === 100) {
            if ($request->marcar_cumplida === true) {
                return $this->completar($request, $obligacion);
            }
        }

        $resultado = $this->service->update($obligacion, $data);

        if (!$resultado['success']) {
            return response()->json($resultado, 422);
        }

        return response()->json($resultado);
    }

    /**
     * Obtiene el calendario de obligaciones del usuario.
     */
    public function calendario(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('obligaciones.view_own'), 403, 'No tienes permisos para ver tus obligaciones');

        $mes = $request->input('mes', now()->month);
        $año = $request->input('año', now()->year);

        $obligaciones = ObligacionContrato::misObligaciones()
                                         ->whereNotNull('fecha_vencimiento')
                                         ->whereYear('fecha_vencimiento', $año)
                                         ->whereMonth('fecha_vencimiento', $mes)
                                         ->with(['contrato', 'padre'])
                                         ->get()
                                         ->groupBy(function ($obligacion) {
                                             return $obligacion->fecha_vencimiento->format('Y-m-d');
                                         });

        return Inertia::render('Modules/Proyectos/User/MisObligaciones/Calendario', [
            'obligaciones' => $obligaciones,
            'mes' => $mes,
            'año' => $año,
        ]);
    }

    /**
     * Obtiene las estadísticas del usuario.
     */
    protected function getEstadisticasUsuario(): array
    {
        $userId = auth()->id();

        $query = ObligacionContrato::where('responsable_id', $userId);

        return [
            'total' => $query->count(),
            'pendientes' => (clone $query)->pendientes()->count(),
            'en_progreso' => (clone $query)->where('estado', 'en_progreso')->count(),
            'cumplidas' => (clone $query)->cumplidas()->count(),
            'vencidas' => (clone $query)->vencidas()->count(),
            'proximas_vencer' => (clone $query)->proximasVencer()->count(),
            'alta_prioridad' => (clone $query)->altaPrioridad()->whereNotIn('estado', ['cumplida', 'cancelada'])->count(),
            'porcentaje_cumplimiento' => $query->count() > 0
                ? round((clone $query)->cumplidas()->count() / $query->count() * 100)
                : 0,
            'promedio_progreso' => (clone $query)->whereNotIn('estado', ['cumplida', 'cancelada'])
                ->avg('porcentaje_cumplimiento') ?? 0
        ];
    }

    /**
     * Obtiene las obligaciones críticas del usuario.
     */
    protected function getObligacionesCriticasUsuario()
    {
        return ObligacionContrato::with(['contrato'])
                                ->where('responsable_id', auth()->id())
                                ->where(function ($q) {
                                    $q->vencidas()
                                      ->orWhere(function ($q2) {
                                          $q2->proximasVencer(3); // 3 días
                                      });
                                })
                                ->orderByRaw("FIELD(prioridad, 'alta', 'media', 'baja')")
                                ->orderBy('fecha_vencimiento', 'asc')
                                ->limit(10)
                                ->get();
    }
}