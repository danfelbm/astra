<?php

namespace Modules\Votaciones\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\AdminController;
use Modules\Elecciones\Models\Convocatoria;
use Modules\Elecciones\Models\PeriodoElectoral;
use Modules\Votaciones\Models\Categoria;
use Modules\Votaciones\Models\Votacion;
use Modules\Votaciones\Http\Requests\Admin\StoreVotacionRequest;
use Modules\Votaciones\Http\Requests\Admin\UpdateVotacionRequest;
use Modules\Votaciones\Http\Requests\Admin\ManageVotantesRequest;
use Modules\Votaciones\Repositories\VotacionRepository;
use Modules\Votaciones\Services\VotacionService;
use Modules\Votaciones\Services\VotacionVoterService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VotacionController extends AdminController
{
    public function __construct(
        private VotacionRepository $repository,
        private VotacionService $service,
        private VotacionVoterService $voterService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('votaciones.view'), 403, 'No tienes permisos para ver votaciones');

        $votaciones = $this->repository->getAllPaginated($request);
        $categorias = Categoria::activas()->get();

        return Inertia::render('Modules/Votaciones/Admin/Index', [
            'votaciones' => $votaciones,
            'categorias' => $categorias,
            'filters' => $request->only(['estado', 'categoria_id', 'search', 'advanced_filters']),
            'filterFieldsConfig' => $this->repository->getFilterFieldsConfig(),
            'canCreate' => auth()->user()->can('votaciones.create'),
            'canEdit' => auth()->user()->can('votaciones.edit'),
            'canDelete' => auth()->user()->can('votaciones.delete'),
            'canManageVoters' => auth()->user()->can('votaciones.manage_voters'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('votaciones.create'), 403, 'No tienes permisos para crear votaciones');

        $categorias = Categoria::activas()->get();

        // Obtener cargos y períodos para el filtro de perfil_candidatura
        $cargos = \Modules\Elecciones\Models\Cargo::activos()
            ->soloCargos()
            ->get()
            ->map(function ($cargo) {
                return [
                    'id' => $cargo->id,
                    'nombre' => $cargo->nombre,
                    'ruta_jerarquica' => $cargo->getRutaJerarquica(),
                    'es_cargo' => $cargo->es_cargo,
                ];
            });

        $periodosElectorales = PeriodoElectoral::activos()
            ->disponibles()
            ->get()
            ->map(function ($periodo) {
                return [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre,
                    'fecha_inicio' => $periodo->fecha_inicio->toDateTimeString(),
                    'fecha_fin' => $periodo->fecha_fin->toDateTimeString(),
                ];
            });

        // Obtener convocatorias disponibles (activas o con postulaciones aprobadas)
        $convocatorias = Convocatoria::with(['cargo', 'periodoElectoral'])
            ->whereHas('postulaciones', function ($q) {
                $q->where('estado', 'aceptada');
            })
            ->get()
            ->map(function ($convocatoria) {
                return [
                    'id' => $convocatoria->id,
                    'nombre' => $convocatoria->nombre,
                    'cargo' => [
                        'id' => $convocatoria->cargo ? $convocatoria->cargo->id : null,
                        'nombre' => $convocatoria->cargo ? $convocatoria->cargo->nombre : null,
                        'ruta_jerarquica' => $convocatoria->cargo ? $convocatoria->cargo->getRutaJerarquica() : null,
                    ],
                    'periodo_electoral' => [
                        'id' => $convocatoria->periodoElectoral ? $convocatoria->periodoElectoral->id : null,
                        'nombre' => $convocatoria->periodoElectoral ? $convocatoria->periodoElectoral->nombre : null,
                    ],
                    'estado_temporal' => $convocatoria->getEstadoTemporal(),
                    'postulaciones_aprobadas' => $convocatoria->postulaciones()->where('estado', 'aceptada')->count(),
                ];
            });

        return Inertia::render('Modules/Votaciones/Admin/Form', [
            'categorias' => $categorias,
            'votacion' => null,
            'cargos' => $cargos,
            'periodosElectorales' => $periodosElectorales,
            'convocatorias' => $convocatorias,
            'canManageVoters' => auth()->user()->can('votaciones.manage_voters'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVotacionRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->route('admin.votaciones.index')
            ->with('success', 'Votación creada exitosamente.');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Votacion $votacione)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('votaciones.edit'), 403, 'No tienes permisos para editar votaciones');

        // Nueva validación: Solo impedir edición de votaciones finalizadas
        if ($votacione->estado === 'finalizada') {
            return redirect()
                ->route('admin.votaciones.index')
                ->with('error', 'No se puede editar una votación finalizada.');
        }

        $categorias = Categoria::activas()->get();

        // Cargar solo los primeros 50 votantes para mostrar inicialmente
        $votantesIniciales = $votacione->votantes()
            ->select('users.id', 'users.name', 'users.email', 'users.documento_identidad', 'users.telefono')
            ->take(50)
            ->get();

        // Convertir fechas de UTC a zona horaria local para mostrar al usuario
        $votacionParaFrontend = $votacione->toArray();
        if ($votacione->fecha_inicio) {
            $votacionParaFrontend['fecha_inicio'] = Carbon::parse($votacione->fecha_inicio)
                ->setTimezone($votacione->timezone)
                ->format('Y-m-d\TH:i');
        }
        if ($votacione->fecha_fin) {
            $votacionParaFrontend['fecha_fin'] = Carbon::parse($votacione->fecha_fin)
                ->setTimezone($votacione->timezone)
                ->format('Y-m-d\TH:i');
        }
        if ($votacione->fecha_publicacion_resultados) {
            $votacionParaFrontend['fecha_publicacion_resultados'] = Carbon::parse($votacione->fecha_publicacion_resultados)
                ->setTimezone($votacione->timezone)
                ->format('Y-m-d\TH:i');
        }
        if ($votacione->limite_censo) {
            $votacionParaFrontend['limite_censo'] = Carbon::parse($votacione->limite_censo)
                ->setTimezone($votacione->timezone)
                ->format('Y-m-d\TH:i');
        }
        // Incluir mensaje_limite_censo si existe
        $votacionParaFrontend['mensaje_limite_censo'] = $votacione->mensaje_limite_censo;

        // Incluir solo los primeros 50 votantes y el total
        $votacionParaFrontend['votantes'] = $votantesIniciales;
        $votacionParaFrontend['votantes_total'] = $votacione->votantes()->count();

        // Obtener cargos y períodos para el filtro de perfil_candidatura
        $cargos = \Modules\Elecciones\Models\Cargo::activos()
            ->soloCargos()
            ->get()
            ->map(function ($cargo) {
                return [
                    'id' => $cargo->id,
                    'nombre' => $cargo->nombre,
                    'ruta_jerarquica' => $cargo->getRutaJerarquica(),
                    'es_cargo' => $cargo->es_cargo,
                ];
            });

        $periodosElectorales = PeriodoElectoral::activos()
            ->disponibles()
            ->get()
            ->map(function ($periodo) {
                return [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre,
                    'fecha_inicio' => $periodo->fecha_inicio->toDateTimeString(),
                    'fecha_fin' => $periodo->fecha_fin->toDateTimeString(),
                ];
            });

        // Obtener convocatorias disponibles (activas o con postulaciones aprobadas)
        $convocatorias = Convocatoria::with(['cargo', 'periodoElectoral'])
            ->whereHas('postulaciones', function ($q) {
                $q->where('estado', 'aceptada');
            })
            ->get()
            ->map(function ($convocatoria) {
                return [
                    'id' => $convocatoria->id,
                    'nombre' => $convocatoria->nombre,
                    'cargo' => [
                        'id' => $convocatoria->cargo ? $convocatoria->cargo->id : null,
                        'nombre' => $convocatoria->cargo ? $convocatoria->cargo->nombre : null,
                        'ruta_jerarquica' => $convocatoria->cargo ? $convocatoria->cargo->getRutaJerarquica() : null,
                    ],
                    'periodo_electoral' => [
                        'id' => $convocatoria->periodoElectoral ? $convocatoria->periodoElectoral->id : null,
                        'nombre' => $convocatoria->periodoElectoral ? $convocatoria->periodoElectoral->nombre : null,
                    ],
                    'estado_temporal' => $convocatoria->getEstadoTemporal(),
                    'postulaciones_aprobadas' => $convocatoria->postulaciones()->where('estado', 'aceptada')->count(),
                ];
            });

        return Inertia::render('Modules/Votaciones/Admin/Form', [
            'categorias' => $categorias,
            'votacion' => $votacionParaFrontend,
            'cargos' => $cargos,
            'periodosElectorales' => $periodosElectorales,
            'convocatorias' => $convocatorias,
            'canManageVoters' => auth()->user()->can('votaciones.manage_voters'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVotacionRequest $request, Votacion $votacione): RedirectResponse
    {
        // Nueva validación: Solo impedir edición de votaciones finalizadas
        if ($votacione->estado === 'finalizada') {
            return back()->with('error', 'No se puede editar una votación finalizada.');
        }

        $this->service->update($votacione, $request->validated());

        return redirect()
            ->route('admin.votaciones.index')
            ->with('success', 'Votación actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Votacion $votacione): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('votaciones.delete'), 403, 'No tienes permisos para eliminar votaciones');

        $result = $this->service->delete($votacione);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.votaciones.index')
            ->with('success', $result['message']);
    }

    /**
     * Toggle status between borrador and activa
     */
    public function toggleStatus(Request $request, Votacion $votacione): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('votaciones.edit'), 403, 'No tienes permisos para cambiar el estado de votaciones');

        $result = $this->service->toggleStatus($votacione, $request->input('estado'));

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Get assigned voters with pagination and search
     */
    public function getAssignedVoters(Request $request, Votacion $votacione)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('votaciones.view'), 403, 'No tienes permisos para ver votantes');

        $query = (string) $request->input('query', '');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 50);

        $votantes = $this->repository->getAssignedVoters($votacione, $query, $page, $perPage);

        return response()->json([
            'data' => $votantes->items(),
            'total' => $votantes->total(),
            'per_page' => $votantes->perPage(),
            'current_page' => $votantes->currentPage(),
            'last_page' => $votantes->lastPage(),
            'has_more' => $votantes->hasMorePages(),
        ]);
    }

    /**
     * Search users for voting assignment with pagination
     */
    public function searchUsers(Request $request, Votacion $votacione)
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('votaciones.manage_voters'), 403, 'No tienes permisos para gestionar votantes');

        $search = (string) $request->input('search', $request->input('query', ''));
        $perPage = $request->input('per_page', 50);

        $usersPaginated = $this->repository->searchAvailableUsers($votacione, $search, $perPage);

        return response()->json([
            'users' => $usersPaginated->items(),
            'data' => $usersPaginated->items(),
            'current_page' => $usersPaginated->currentPage(),
            'last_page' => $usersPaginated->lastPage(),
            'per_page' => $usersPaginated->perPage(),
            'total' => $usersPaginated->total(),
            'has_more' => $usersPaginated->hasMorePages(),
        ]);
    }

    /**
     * Manage voters for a specific votacion
     */
    public function manageVotantes(ManageVotantesRequest $request, Votacion $votacione)
    {
        if ($request->isMethod('GET')) {
            // Solo obtener votantes asignados (no cargar todos los disponibles)
            $votantesAsignados = $votacione->votantes()
                ->select('users.id', 'users.name', 'users.email', 'users.documento_identidad', 'users.telefono')
                ->get();

            return response()->json([
                'votantes_asignados' => $votantesAsignados,
                'votantes_disponibles' => [],
            ]);
        }

        if ($request->isMethod('POST')) {
            $this->voterService->assignVoters($votacione, $request->votante_ids);
            return back()->with('success', 'Votantes asignados exitosamente.');
        }

        if ($request->isMethod('DELETE')) {
            $this->voterService->removeVoter($votacione, $request->votante_id);
            return back()->with('success', 'Votante removido exitosamente.');
        }
    }

    /**
     * Importar votantes desde un archivo CSV (usando jobs en background)
     * 
     * @deprecated desde v2.0 - Usar ImportController::storeWithVotacion con el nuevo CsvImportWizard
     */
    public function importarVotantes(Request $request, Votacion $votacione): RedirectResponse
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('votaciones.manage_voters'), 403, 'No tienes permisos para importar votantes');

        // Log de uso de método deprecado para futuro análisis
        \Log::warning('Uso de método deprecado VotacionController::importarVotantes', [
            'votacion_id' => $votacione->id,
            'user_id' => Auth::id(),
            'user_email' => Auth::user()->email ?? 'N/A',
            'timestamp' => now()->toIso8601String(),
        ]);

        // ... resto del código deprecado si es necesario mantenerlo ...
        return back()->with('warning', 'Esta funcionalidad está obsoleta. Por favor use el nuevo asistente de importación.');
    }
}