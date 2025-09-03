<?php

namespace Modules\Asamblea\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\Base\AdminController;
use Modules\Asamblea\Http\Requests\Admin\StoreAsambleaRequest;
use Modules\Asamblea\Http\Requests\Admin\UpdateAsambleaRequest;
use Modules\Asamblea\Repositories\AsambleaRepository;
use Modules\Asamblea\Services\AsambleaParticipantService;
use Modules\Asamblea\Services\AsambleaService;
use Modules\Asamblea\Http\Requests\Admin\ManageParticipantesRequest;
use Modules\Asamblea\Models\Asamblea;
use Modules\Core\Models\User;
use Modules\Geografico\Models\Territorio;
use Modules\Geografico\Models\Departamento;
use Modules\Geografico\Models\Municipio;
use Modules\Geografico\Models\Localidad;
use Modules\Votaciones\Models\Votacion;
use Modules\Core\Traits\HasAdvancedFilters;
use Modules\Core\Traits\HasGeographicFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AsambleaController extends AdminController
{
    use HasAdvancedFilters, HasGeographicFilters;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, AsambleaRepository $repository): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('asambleas.view'), 403, 'No tienes permisos para ver asambleas');

        $asambleas = $repository->getAllPaginated($request, 15);

        return Inertia::render('Modules/Asamblea/Admin/Index', [
            'asambleas' => $asambleas,
            'filters' => $request->only(['tipo', 'estado', 'activo', 'search', 'advanced_filters']),
            'filterFieldsConfig' => $repository->getIndexFilterFieldsConfig(),
            'canCreate' => auth()->user()->can('asambleas.create'),
            'canEdit' => auth()->user()->can('asambleas.edit'),
            'canDelete' => auth()->user()->can('asambleas.delete'),
            'canManageParticipants' => auth()->user()->can('asambleas.manage_participants'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('asambleas.create'), 403, 'No tienes permisos para crear asambleas');

        return Inertia::render('Modules/Asamblea/Admin/Form', [
            'asamblea' => null,
            'territorios' => Territorio::all(),
            'departamentos' => Departamento::all(),
            'municipios' => Municipio::all(),
            'localidades' => Localidad::all(),
            'votaciones' => Votacion::select('id', 'titulo', 'descripcion', 'estado', 'fecha_inicio', 'fecha_fin')
                ->where('estado', 'activa')
                ->orderBy('titulo')
                ->get(),
            'asambleaVotaciones' => [],
            'canManageParticipants' => auth()->user()->can('asambleas.manage_participants'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAsambleaRequest $request, AsambleaService $asambleaService): RedirectResponse
    {
        $data = $request->getValidatedData();
        
        // Agregar votacion_ids del request si existen
        if (!empty($request->votacion_ids)) {
            $data['votacion_ids'] = $request->votacion_ids;
        }

        $result = $asambleaService->create($data);
        
        $messageType = ($result['zoom_result'] && !$result['zoom_result']['success']) ? 'warning' : 'success';

        return redirect()->route('admin.asambleas.index')
            ->with($messageType, $result['message']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Asamblea $asamblea, AsambleaRepository $repository): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('asambleas.view'), 403, 'No tienes permisos para ver asambleas');

        $asamblea->load([
            'territorio', 
            'departamento', 
            'municipio', 
            'localidad'
        ]);

        return Inertia::render('Modules/Asamblea/Admin/Show', [
            'asamblea' => array_merge($asamblea->toArray(), [
                'estado_label' => $asamblea->getEstadoLabel(),
                'estado_color' => $asamblea->getEstadoColor(),
                'tipo_label' => $asamblea->getTipoLabel(),
                'ubicacion_completa' => $asamblea->getUbicacionCompleta(),
                'duracion' => $asamblea->getDuracion(),
                'tiempo_restante' => $asamblea->getTiempoRestante(),
                'rango_fechas' => $asamblea->getRangoFechas(),
                'alcanza_quorum' => $asamblea->alcanzaQuorum(),
                'asistentes_count' => $asamblea->getAsistentesCount(),
                'participantes_count' => $asamblea->getParticipantesCount(),
            ]),
            'puede_gestionar_participantes' => Auth::user()->can('asambleas.manage_participants'),
            'filterFieldsConfig' => $repository->getParticipantesFilterFieldsConfig(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Asamblea $asamblea): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('asambleas.edit'), 403, 'No tienes permisos para editar asambleas');

        return Inertia::render('Modules/Asamblea/Admin/Form', [
            'asamblea' => $asamblea,
            'territorios' => Territorio::all(),
            'departamentos' => Departamento::all(),
            'municipios' => Municipio::all(),
            'localidades' => Localidad::all(),
            'votaciones' => Votacion::select('id', 'titulo', 'descripcion', 'estado', 'fecha_inicio', 'fecha_fin')
                ->where('estado', 'activa')
                ->orderBy('titulo')
                ->get(),
            'asambleaVotaciones' => $asamblea->votaciones()->pluck('votaciones.id')->toArray(),
            'canManageParticipants' => auth()->user()->can('asambleas.manage_participants'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAsambleaRequest $request, Asamblea $asamblea, AsambleaService $asambleaService): RedirectResponse
    {
        $data = $request->getValidatedData();
        
        // Agregar votacion_ids del request si existen
        if (isset($request->votacion_ids)) {
            $data['votacion_ids'] = $request->votacion_ids;
        }

        $result = $asambleaService->update($asamblea, $data);

        if (!$result['success']) {
            return back()->withErrors([$result['field'] => $result['message']]);
        }

        return redirect()->route('admin.asambleas.index')
            ->with('success', $result['message']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asamblea $asamblea, AsambleaService $asambleaService): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('asambleas.delete'), 403, 'No tienes permisos para eliminar asambleas');

        $result = $asambleaService->delete($asamblea);

        if (!$result['success']) {
            return redirect()->route('admin.asambleas.index')
                ->with('error', $result['message']);
        }

        return redirect()->route('admin.asambleas.index')
            ->with('success', $result['message']);
    }

    /**
     * Obtener participantes paginados con filtros
     */
    public function getParticipantes(Request $request, Asamblea $asamblea, AsambleaRepository $repository)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('asambleas.view'), 403, 'No tienes permisos para ver participantes');

        $participantes = $repository->getParticipantesPaginated($asamblea, $request, 20);

        return response()->json([
            'participantes' => $participantes,
            'filterFieldsConfig' => $repository->getParticipantesFilterFieldsConfig(),
        ]);
    }


    /**
     * Gestionar participantes de la asamblea
     */
    public function manageParticipantes(Request $request, Asamblea $asamblea, AsambleaParticipantService $participantService)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('asambleas.manage_participants'), 403, 'No tienes permisos para gestionar participantes');

        if ($request->isMethod('GET')) {
            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            
            return response()->json(
                $participantService->getAvailableUsers($asamblea, $search, $page)
            );
        }

        // Para POST/PUT/DELETE usamos Form Request con validaciones
        $validatedRequest = app(ManageParticipantesRequest::class);

        if ($request->isMethod('POST')) {
            $participantService->assignParticipants(
                $asamblea, 
                $validatedRequest->getParticipanteIds(), 
                $validatedRequest->getTipoParticipacion()
            );
            return back()->with('success', 'Participantes asignados exitosamente.');
        }

        if ($request->isMethod('DELETE')) {
            $participantService->removeParticipant(
                $asamblea, 
                $validatedRequest->getParticipanteId()
            );
            return back()->with('success', 'Participante removido exitosamente.');
        }

        if ($request->isMethod('PUT')) {
            $participantService->updateParticipant(
                $asamblea,
                $validatedRequest->getParticipanteId(),
                $validatedRequest->getTipoParticipacion(),
                $validatedRequest->getAsistio()
            );
            return back()->with('success', 'Participante actualizado exitosamente.');
        }
    }

    /**
     * Sincronizar participantes de asamblea a votación
     */
    public function syncParticipantsToVotacion(Request $request, Asamblea $asamblea, Votacion $votacion, AsambleaService $asambleaService): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('asambleas.sync_participants'), 403, 'No tienes permisos para sincronizar participantes');

        $result = $asambleaService->syncParticipantsToVotacion($asamblea, $votacion);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'job_id' => $result['job_id'] ?? null
        ], $result['status']);
    }

    /**
     * Obtener el estado de un job de sincronización
     */
    public function getSyncJobStatus(string $jobId, AsambleaService $asambleaService): JsonResponse
    {
        $result = $asambleaService->getSyncJobStatus($jobId);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null
        ], $result['status']);
    }

    /**
     * Obtener votaciones de una asamblea
     */
    public function getVotaciones(Asamblea $asamblea, AsambleaRepository $repository): JsonResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('asambleas.view'), 403, 'No tienes permisos para ver asambleas');

        $votaciones = $repository->getVotacionesWithSyncData($asamblea);

        return response()->json([
            'success' => true,
            'data' => $votaciones
        ]);
    }
}