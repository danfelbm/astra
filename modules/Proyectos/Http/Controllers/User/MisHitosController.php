<?php

namespace Modules\Proyectos\Http\Controllers\User;

use Modules\Core\Http\Controllers\UserController;
use Modules\Core\Traits\HasAdvancedFilters;
use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Models\Entregable;
use Modules\Proyectos\Services\EntregableService;
use Modules\Proyectos\Repositories\HitoRepository;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\Inertia;

class MisHitosController extends UserController
{
    use HasAdvancedFilters;

    public function __construct(
        private EntregableService $entregableService,
        private HitoRepository $hitoRepository
    ) {
        parent::__construct();
    }

    /**
     * Muestra la lista de hitos asignados al usuario.
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('hitos.view_own'), 403, 'No tienes permisos para ver hitos');

        $user = auth()->user();

        // Obtener hitos donde el usuario es responsable o tiene entregables asignados
        $hitos = $this->hitoRepository->getMisHitos($request);

        // Obtener estadísticas del usuario
        $estadisticas = [
            'total' => $hitos->total(),
            'pendientes' => Hito::whereHas('entregables', function ($q) use ($user) {
                $q->where('responsable_id', $user->id)
                  ->orWhereHas('usuarios', function ($q2) use ($user) {
                      $q2->where('users.id', $user->id);
                  });
            })->where('estado', 'pendiente')->count(),
            'en_progreso' => Hito::whereHas('entregables', function ($q) use ($user) {
                $q->where('responsable_id', $user->id)
                  ->orWhereHas('usuarios', function ($q2) use ($user) {
                      $q2->where('users.id', $user->id);
                  });
            })->where('estado', 'en_progreso')->count(),
            'completados' => Hito::whereHas('entregables', function ($q) use ($user) {
                $q->where('responsable_id', $user->id)
                  ->orWhereHas('usuarios', function ($q2) use ($user) {
                      $q2->where('users.id', $user->id);
                  });
            })->where('estado', 'completado')->count(),
            'entregables_pendientes' => Entregable::where(function ($q) use ($user) {
                $q->where('responsable_id', $user->id)
                  ->orWhereHas('usuarios', function ($q2) use ($user) {
                      $q2->where('users.id', $user->id);
                  });
            })->where('estado', 'pendiente')->count(),
        ];

        return Inertia::render('Modules/Proyectos/User/MisHitos/Index', [
            'hitos' => $hitos,
            'filters' => $request->only(['search', 'estado', 'proyecto_id']),
            'estadisticas' => $estadisticas,
            'canView' => auth()->user()->can('hitos.view_own'),
            'canComplete' => auth()->user()->can('hitos.complete_own'),
            'canUpdateProgress' => auth()->user()->can('hitos.update_progress'),
        ]);
    }

    /**
     * Muestra el detalle de un hito específico asignado al usuario.
     */
    public function show(Request $request, Hito $hito): Response
    {
        $user = auth()->user();

        // Verificar que el usuario tiene acceso a este hito
        $tieneAcceso = $hito->responsable_id === $user->id ||
            $hito->entregables()->where(function ($q) use ($user) {
                $q->where('responsable_id', $user->id)
                  ->orWhereHas('usuarios', function ($q2) use ($user) {
                      $q2->where('users.id', $user->id);
                  });
            })->exists();

        // Verificar permisos y acceso al hito
        abort_unless(auth()->user()->can('hitos.view_own'), 403, 'No tienes permisos para ver hitos');
        abort_unless($tieneAcceso, 403, 'No tienes acceso a este hito');

        // Cargar el hito con sus relaciones
        $hito->load([
            'proyecto:id,nombre,estado',
            'responsable:id,name,email',
            'entregables' => function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('responsable_id', $user->id)
                      ->orWhereHas('usuarios', function ($q2) use ($user) {
                          $q2->where('users.id', $user->id);
                      });
                })->with(['responsable:id,name,email', 'usuarios:id,name,email'])
                  ->orderBy('orden');
            }
        ]);

        // Obtener entregables del usuario
        $misEntregables = $hito->entregables->filter(function ($entregable) use ($user) {
            return $entregable->responsable_id === $user->id ||
                   $entregable->usuarios->contains('id', $user->id);
        });

        // Estadísticas del hito para el usuario
        $estadisticas = [
            'total_entregables' => $misEntregables->count(),
            'entregables_completados' => $misEntregables->where('estado', 'completado')->count(),
            'entregables_pendientes' => $misEntregables->where('estado', 'pendiente')->count(),
            'entregables_en_progreso' => $misEntregables->where('estado', 'en_progreso')->count(),
            'porcentaje_personal' => $misEntregables->count() > 0
                ? round(($misEntregables->where('estado', 'completado')->count() / $misEntregables->count()) * 100)
                : 0,
            'dias_restantes' => $hito->dias_restantes,
        ];

        return Inertia::render('Modules/Proyectos/User/MisHitos/Show', [
            'hito' => $hito,
            'misEntregables' => $misEntregables->values(),
            'estadisticas' => $estadisticas,
            'canComplete' => auth()->user()->can('hitos.complete_own'),
            'canUpdateProgress' => auth()->user()->can('hitos.update_progress'),
            'canCompleteEntregables' => auth()->user()->can('entregables.complete'),
        ]);
    }

    /**
     * Marca un entregable como completado.
     */
    public function completarEntregable(Request $request, Hito $hito, Entregable $entregable): RedirectResponse
    {
        $user = auth()->user();

        // Verificar permisos
        abort_unless(auth()->user()->can('entregables.complete'), 403, 'No tienes permisos para completar entregables');
        abort_unless($entregable->puedeSerCompletadoPor($user), 403, 'No tienes permisos para completar este entregable');

        $request->validate([
            'notas' => 'nullable|string|max:1000'
        ]);

        $result = $this->entregableService->marcarComoCompletado(
            $entregable,
            $user->id,
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
     * Actualiza el estado de un entregable asignado al usuario.
     */
    public function actualizarEstadoEntregable(Request $request, Hito $hito, Entregable $entregable): RedirectResponse
    {
        $user = auth()->user();

        // Verificar permisos
        abort_unless(auth()->user()->can('hitos.update_progress'), 403, 'No tienes permisos para actualizar el estado de entregables');
        abort_unless($entregable->puedeSerEditadoPor($user), 403, 'No tienes permisos para actualizar este entregable');

        $request->validate([
            'estado' => 'required|in:pendiente,en_progreso,completado',
            'notas' => 'nullable|string|max:1000'
        ]);

        $entregable->estado = $request->estado;

        if ($request->estado === 'completado') {
            $entregable->completado_at = now();
            $entregable->completado_por = $user->id;
            $entregable->notas_completado = $request->notas;
        }

        $entregable->save();

        // Actualizar porcentaje del hito
        $hito->calcularPorcentajeCompletado();

        return redirect()
            ->back()
            ->with('success', 'Estado del entregable actualizado');
    }

    /**
     * Muestra el timeline de hitos del usuario.
     */
    public function timeline(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('hitos.view_own'), 403, 'No tienes permisos para ver el timeline de hitos');

        $user = auth()->user();

        // Obtener timeline de hitos
        $timeline = $this->hitoRepository->getTimelineUsuario($user->id);

        return Inertia::render('Modules/Proyectos/User/MisHitos/Timeline', [
            'timeline' => $timeline,
            'year' => $request->get('year', date('Y')),
            'month' => $request->get('month', date('m')),
            'canView' => auth()->user()->can('hitos.view_own'),
        ]);
    }
}