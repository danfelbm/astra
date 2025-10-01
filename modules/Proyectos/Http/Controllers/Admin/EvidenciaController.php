<?php

namespace Modules\Proyectos\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\AdminController;
use Modules\Proyectos\Models\Contrato;
use Modules\Proyectos\Models\Evidencia;
use Inertia\Inertia;
use Inertia\Response;

class EvidenciaController extends AdminController
{
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
}