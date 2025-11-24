<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\AdminController;
use Modules\Proyectos\Models\Contrato;
use Modules\Proyectos\Models\Evidencia;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Services\EvidenciaService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class EvidenciaController extends AdminController
{
    public function __construct(
        private EvidenciaService $service
    ) {
        parent::__construct();
    }

    /**
     * Muestra una evidencia específica (solo lectura para admin).
     */
    public function show(Contrato $contrato, Evidencia $evidencia): Response
    {
        // Verificar permisos de admin
        abort_unless(
            auth()->user()->can('evidencias.view') || auth()->user()->can('contratos.view'),
            403,
            'No tienes permiso para ver evidencias'
        );

        // Verificar que la evidencia pertenece al contrato
        if ($evidencia->obligacion->contrato_id !== $contrato->id) {
            abort(404, 'La evidencia no pertenece a este contrato');
        }

        // Cargar relaciones necesarias
        $evidencia->load([
            'usuario',
            'obligacion',
            'entregables.hito.proyecto',
            'revisor'
        ]);

        return Inertia::render('Modules/Proyectos/Admin/Evidencias/Show', [
            'contrato' => $contrato->load('proyecto'),
            'evidencia' => $evidencia
        ]);
    }

    /**
     * Aprueba una evidencia desde el contexto de proyecto.
     */
    public function aprobarDesdeProyecto(Request $request, Proyecto $proyecto, Evidencia $evidencia): JsonResponse
    {
        // Verificar que la evidencia pertenece a un contrato del proyecto
        $contrato = Contrato::where('proyecto_id', $proyecto->id)
            ->whereHas('obligaciones.evidencias', function ($query) use ($evidencia) {
                $query->where('evidencias.id', $evidencia->id);
            })
            ->first();

        if (!$contrato) {
            return response()->json([
                'success' => false,
                'message' => 'La evidencia no pertenece a este proyecto'
            ], 404);
        }

        // Verificar permisos
        $this->authorize('evidencias.aprobar', $evidencia);

        $result = $this->service->aprobar(
            $evidencia,
            $request->input('observaciones')
        );

        return response()->json($result);
    }

    /**
     * Rechaza una evidencia desde el contexto de proyecto.
     */
    public function rechazarDesdeProyecto(Request $request, Proyecto $proyecto, Evidencia $evidencia): JsonResponse
    {
        // Verificar que la evidencia pertenece a un contrato del proyecto
        $contrato = Contrato::where('proyecto_id', $proyecto->id)
            ->whereHas('obligaciones.evidencias', function ($query) use ($evidencia) {
                $query->where('evidencias.id', $evidencia->id);
            })
            ->first();

        if (!$contrato) {
            return response()->json([
                'success' => false,
                'message' => 'La evidencia no pertenece a este proyecto'
            ], 404);
        }

        // Verificar permisos
        $this->authorize('evidencias.rechazar', $evidencia);

        $result = $this->service->rechazar(
            $evidencia,
            $request->input('observaciones')
        );

        return response()->json($result);
    }

    /**
     * Cambia el estado de una evidencia directamente (solo admin).
     */
    public function cambiarEstadoDesdeProyecto(Request $request, Proyecto $proyecto, Evidencia $evidencia): RedirectResponse
    {
        // Verificar que la evidencia pertenece a un contrato del proyecto
        $contrato = Contrato::where('proyecto_id', $proyecto->id)
            ->whereHas('obligaciones.evidencias', function ($query) use ($evidencia) {
                $query->where('evidencias.id', $evidencia->id);
            })
            ->first();

        if (!$contrato) {
            return back()->withErrors(['error' => 'La evidencia no pertenece a este proyecto']);
        }

        // Validar el estado
        $request->validate([
            'estado' => 'required|in:pendiente,aprobada,rechazada',
            'observaciones' => 'nullable|string'
        ]);

        $nuevoEstado = $request->input('estado');
        $observaciones = $request->input('observaciones');

        // Cambiar el estado según lo solicitado
        if ($nuevoEstado === 'aprobada') {
            $result = $this->service->aprobar($evidencia, $observaciones);
            return back()->with('success', $result['message'] ?? 'Evidencia aprobada exitosamente');
        } elseif ($nuevoEstado === 'rechazada') {
            $result = $this->service->rechazar($evidencia, $observaciones);
            return back()->with('error', $result['message'] ?? 'Evidencia rechazada');
        } else {
            // Volver a pendiente
            $evidencia->estado = 'pendiente';
            $evidencia->observaciones_admin = $observaciones;
            $evidencia->revisado_at = null;
            $evidencia->revisado_por = null;
            $evidencia->save();

            return back()->with('info', 'Estado cambiado a pendiente');
        }
    }
}