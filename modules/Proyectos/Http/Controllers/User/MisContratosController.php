<?php

namespace Modules\Proyectos\Http\Controllers\User;

use Modules\Core\Http\Controllers\UserController;
use Modules\Proyectos\Models\Contrato;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Repositories\ContratoRepository;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;

class MisContratosController extends UserController
{
    public function __construct(
        private ContratoRepository $repository
    ) {
        parent::__construct();
    }

    /**
     * Muestra los contratos de los proyectos del usuario.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();

        // Obtener proyectos donde el usuario es responsable o participante
        $proyectosIds = Proyecto::where('responsable_id', $user->id)
            ->orWhereHas('participantes', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id');

        // Obtener contratos donde el usuario tiene algún tipo de relación
        $query = Contrato::with(['proyecto', 'responsable', 'contraparteUser'])
            ->where(function ($q) use ($proyectosIds, $user) {
                $q->whereIn('proyecto_id', $proyectosIds)
                  ->orWhere('responsable_id', $user->id)
                  ->orWhere('contraparte_user_id', $user->id)
                  ->orWhereHas('participantes', function ($query) use ($user) {
                      $query->where('user_id', $user->id);
                  });
            });

        // Aplicar filtros
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->search}%")
                  ->orWhere('descripcion', 'like', "%{$request->search}%");
            });
        }

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->proyecto_id) {
            $query->where('proyecto_id', $request->proyecto_id);
        }

        // Solo contratos activos por defecto
        if (!$request->has('todos')) {
            $query->whereIn('estado', ['activo', 'finalizado']);
        }

        // Clonar query para estadísticas antes de modificar
        $queryStats = clone $query;

        $contratos = $query->orderBy('fecha_inicio', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Calcular estadísticas
        $estadisticas = [
            'total' => (clone $queryStats)->count(),
            'activos' => (clone $queryStats)->where('estado', 'activo')->count(),
            'finalizados' => (clone $queryStats)->where('estado', 'finalizado')->count(),
            'vencidos' => (clone $queryStats)->where('estado', 'activo')
                ->where('fecha_fin', '<', now())
                ->count(),
            'proximos_vencer' => (clone $queryStats)->where('estado', 'activo')
                ->where('fecha_fin', '<=', now()->addDays(30))
                ->where('fecha_fin', '>', now())
                ->count(),
            'monto_total' => (clone $queryStats)->sum('monto_total') ?? 0,
        ];

        // Mis proyectos para filtro
        $misProyectos = Proyecto::whereIn('id', $proyectosIds)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return Inertia::render('Modules/Proyectos/User/MisContratos/Index', [
            'contratos' => $contratos,
            'filtros' => $request->only(['search', 'estado', 'tipo', 'proyecto_id', 'todos']),
            'estadisticas' => $estadisticas,
            'proyectos' => $misProyectos,
            'authPermissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray(),
        ]);
    }

    /**
     * Muestra el detalle de un contrato específico.
     */
    public function show(Contrato $contrato): Response
    {
        $user = auth()->user();

        // Verificar permisos y acceso al contrato
        abort_unless(auth()->user()->can('contratos.view_own'), 403, 'No tienes permisos para ver contratos');
        abort_unless($this->tieneAccesoAlContrato($contrato, $user), 403, 'No tienes acceso a este contrato');

        // Cargar relaciones necesarias
        $contrato->load([
            'proyecto',
            'responsable',
            'contraparteUser',
            'camposPersonalizados.campoPersonalizado'
        ]);

        // Formatear campos personalizados
        $camposPersonalizados = $contrato->camposPersonalizados->map(function ($valor) {
            return [
                'campo' => [
                    'id' => $valor->campoPersonalizado->id,
                    'nombre' => $valor->campoPersonalizado->nombre,
                    'tipo' => $valor->campoPersonalizado->tipo,
                ],
                'valor' => $valor->valor,
                'valor_formateado' => $valor->getValorFormateadoAttribute(),
            ];
        });

        $contrato->campos_personalizados = $camposPersonalizados;

        return Inertia::render('Modules/Proyectos/User/MisContratos/Show', [
            'contrato' => $contrato,
            'puedeEditar' => $this->puedeEditarContrato($contrato, $user),
            'authPermissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray(),
        ]);
    }

    /**
     * Muestra contratos próximos a vencer del usuario.
     */
    public function proximosVencer(): Response
    {
        $user = auth()->user();

        // Obtener proyectos del usuario
        $proyectosIds = Proyecto::where('responsable_id', $user->id)
            ->orWhereHas('participantes', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id');

        $contratos = Contrato::with(['proyecto', 'responsable'])
            ->where(function ($q) use ($proyectosIds, $user) {
                $q->whereIn('proyecto_id', $proyectosIds)
                  ->orWhere('responsable_id', $user->id);
            })
            ->where('estado', 'activo')
            ->where('fecha_fin', '<=', now()->addDays(30))
            ->where('fecha_fin', '>', now())
            ->orderBy('fecha_fin', 'asc')
            ->get();

        return Inertia::render('Modules/Proyectos/User/MisContratos/ProximosVencer', [
            'contratos' => $contratos,
        ]);
    }

    /**
     * Muestra contratos vencidos del usuario.
     */
    public function vencidos(): Response
    {
        $user = auth()->user();

        // Obtener proyectos del usuario
        $proyectosIds = Proyecto::where('responsable_id', $user->id)
            ->orWhereHas('participantes', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id');

        $contratos = Contrato::with(['proyecto', 'responsable'])
            ->where(function ($q) use ($proyectosIds, $user) {
                $q->whereIn('proyecto_id', $proyectosIds)
                  ->orWhere('responsable_id', $user->id);
            })
            ->where('estado', 'activo')
            ->where('fecha_fin', '<', now())
            ->orderBy('fecha_fin', 'desc')
            ->get();

        return Inertia::render('Modules/Proyectos/User/MisContratos/Vencidos', [
            'contratos' => $contratos,
        ]);
    }

    /**
     * Descarga el PDF de un contrato.
     */
    public function descargarPDF(Contrato $contrato)
    {
        $user = auth()->user();

        // Verificar permisos y acceso al contrato
        abort_unless(auth()->user()->can('contratos.download'), 403, 'No tienes permisos para descargar contratos');
        abort_unless($this->tieneAccesoAlContrato($contrato, $user), 403, 'No tienes acceso a este contrato');

        if (!$contrato->archivo_pdf) {
            abort(404, 'Este contrato no tiene archivo PDF');
        }

        $path = storage_path('app/' . $contrato->archivo_pdf);

        if (!file_exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->download($path, 'contrato_' . $contrato->id . '.pdf');
    }

    /**
     * Verifica si el usuario tiene acceso al contrato.
     */
    private function tieneAccesoAlContrato(Contrato $contrato, $user): bool
    {
        // Es responsable del contrato
        if ($contrato->responsable_id === $user->id) {
            return true;
        }

        // Es contraparte del contrato
        if ($contrato->contraparte_user_id === $user->id) {
            return true;
        }

        // Es participante del contrato
        if ($contrato->participantes()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Es responsable del proyecto
        if ($contrato->proyecto->responsable_id === $user->id) {
            return true;
        }

        // Es participante del proyecto
        if ($contrato->proyecto->participantes()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Tiene permiso específico
        if ($user->can('contratos.view_own')) {
            return true;
        }

        return false;
    }

    /**
     * Verifica si el usuario puede editar el contrato.
     */
    private function puedeEditarContrato(Contrato $contrato, $user): bool
    {
        // Solo si es responsable del contrato y tiene el permiso
        if ($contrato->responsable_id === $user->id && $user->can('contratos.edit_own')) {
            return true;
        }

        // O si es responsable del proyecto con permisos especiales
        if ($contrato->proyecto->responsable_id === $user->id && $user->can('contratos.edit_own')) {
            return true;
        }

        return false;
    }
}