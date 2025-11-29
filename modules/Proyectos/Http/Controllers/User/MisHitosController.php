<?php

namespace Modules\Proyectos\Http\Controllers\User;

use Modules\Core\Http\Controllers\UserController;
use Modules\Core\Traits\HasAdvancedFilters;
use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\Entregable;
use Modules\Proyectos\Models\CategoriaEtiqueta;
use Modules\Proyectos\Services\EntregableService;
use Modules\Proyectos\Services\HitoService;
use Modules\Proyectos\Repositories\HitoRepository;
use Modules\Proyectos\Repositories\CampoPersonalizadoRepository;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Inertia\Inertia;

class MisHitosController extends UserController
{
    use HasAdvancedFilters;

    public function __construct(
        private EntregableService $entregableService,
        private HitoService $hitoService,
        private HitoRepository $hitoRepository,
        private CampoPersonalizadoRepository $campoPersonalizadoRepository
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
     * Usa el método genérico cambiarEstado que registra en audit log.
     */
    public function actualizarEstadoEntregable(Request $request, Hito $hito, Entregable $entregable): RedirectResponse
    {
        $user = auth()->user();

        // Verificar permisos
        abort_unless(auth()->user()->can('hitos.update_progress'), 403, 'No tienes permisos para actualizar el estado de entregables');
        abort_unless($entregable->puedeSerEditadoPor($user), 403, 'No tienes permisos para actualizar este entregable');

        $request->validate([
            'estado' => 'required|in:pendiente,en_progreso,completado',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        // Usar el método genérico que registra en audit log
        $entregable->cambiarEstado(
            $request->estado,
            $user->id,
            $request->observaciones
        );

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

    /**
     * Verifica si el usuario puede gestionar hitos del proyecto (es responsable o gestor).
     */
    private function puedeGestionarHitosDelProyecto(Proyecto $proyecto): bool
    {
        $userId = auth()->id();
        return $proyecto->responsable_id === $userId ||
               $proyecto->gestores()->where('user_id', $userId)->exists();
    }

    /**
     * Muestra el formulario para crear un nuevo hito dentro de un proyecto.
     */
    public function create(Request $request, Proyecto $proyecto): Response
    {
        // Solo gestores y responsable del proyecto pueden crear hitos
        abort_unless($this->puedeGestionarHitosDelProyecto($proyecto), 403, 'Solo el responsable o gestores del proyecto pueden crear hitos');

        $proyecto->load(['responsable']);

        // Obtener posibles responsables: participantes del proyecto
        $responsables = $proyecto->participantes()
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);

        // Obtener hitos disponibles como padres
        $hitosDisponibles = $proyecto->hitos()
            ->with('parent:id,nombre')
            ->orderBy('orden')
            ->get()
            ->map(fn($h) => [
                'id' => $h->id,
                'nombre' => $h->nombre,
                'ruta_completa' => $h->ruta_completa ?? $h->nombre,
                'nivel' => $h->nivel ?? 0
            ]);

        // Obtener campos personalizados para hitos
        $camposPersonalizados = $this->campoPersonalizadoRepository->getActivosParaHitos();

        // Obtener categorías de etiquetas disponibles (filtradas para hitos)
        $categorias = CategoriaEtiqueta::with('etiquetas')
            ->activas()
            ->paraHitos()
            ->ordenado()
            ->get();

        return Inertia::render('Modules/Proyectos/User/MisHitos/Create', [
            'proyecto' => $proyecto,
            'responsables' => $responsables,
            'hitosDisponibles' => $hitosDisponibles,
            'camposPersonalizados' => $camposPersonalizados,
            'categorias' => $categorias,
            'estados' => [
                ['value' => 'pendiente', 'label' => 'Pendiente'],
                ['value' => 'en_progreso', 'label' => 'En Progreso'],
            ],
            'siguienteOrden' => ($proyecto->hitos()->max('orden') ?? 0) + 1
        ]);
    }

    /**
     * Guarda un nuevo hito en la base de datos desde el área de usuario.
     */
    public function store(Request $request, Proyecto $proyecto): RedirectResponse
    {
        // Solo gestores y responsable del proyecto pueden crear hitos
        abort_unless($this->puedeGestionarHitosDelProyecto($proyecto), 403, 'Solo el responsable o gestores del proyecto pueden crear hitos');

        // Validar datos
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'estado' => 'nullable|in:pendiente,en_progreso',
            'responsable_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:hitos,id',
            'orden' => 'nullable|integer|min:0',
            'campos_personalizados' => 'nullable|array',
            'etiquetas' => 'nullable|array',
            'etiquetas.*' => 'exists:etiquetas,id',
        ]);

        // Añadir proyecto_id a los datos
        $data = array_merge($validated, [
            'proyecto_id' => $proyecto->id,
            'estado' => $validated['estado'] ?? 'pendiente'
        ]);

        // Usar el servicio para crear el hito
        $result = $this->hitoService->create($data);

        if ($result['success']) {
            return redirect()
                ->route('user.mis-proyectos.show', ['proyecto' => $proyecto->id, 'tab' => 'hitos'])
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Muestra el formulario para editar un hito desde el área de usuario.
     */
    public function edit(Request $request, Proyecto $proyecto, Hito $hito): Response
    {
        // Solo gestores y responsable del proyecto pueden editar hitos
        abort_unless($this->puedeGestionarHitosDelProyecto($proyecto), 403, 'Solo el responsable o gestores del proyecto pueden editar hitos');
        abort_unless($hito->proyecto_id === $proyecto->id, 404, 'El hito no pertenece a este proyecto');

        $hito->load(['responsable:id,name,email', 'etiquetas.categoria']);

        // Obtener posibles responsables: participantes del proyecto
        $responsables = $proyecto->participantes()
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);

        // Obtener hitos disponibles como padres (excepto el hito actual y sus descendientes)
        $hitosDisponibles = $proyecto->hitos()
            ->where('id', '!=', $hito->id)
            ->with('parent:id,nombre')
            ->orderBy('orden')
            ->get()
            ->filter(function($h) use ($hito) {
                return !$hito->esAncestroDe($h);
            })
            ->map(fn($h) => [
                'id' => $h->id,
                'nombre' => $h->nombre,
                'ruta_completa' => $h->ruta_completa ?? $h->nombre,
                'nivel' => $h->nivel ?? 0
            ])
            ->values();

        // Obtener campos personalizados y valores actuales
        $camposPersonalizados = $this->campoPersonalizadoRepository->getActivosParaHitos();
        $valoresCamposPersonalizados = $hito->getCamposPersonalizadosValues();

        // Obtener categorías de etiquetas disponibles (filtradas para hitos)
        $categorias = CategoriaEtiqueta::with('etiquetas')
            ->activas()
            ->paraHitos()
            ->ordenado()
            ->get();

        return Inertia::render('Modules/Proyectos/User/MisHitos/Edit', [
            'proyecto' => $proyecto,
            'hito' => $hito,
            'responsables' => $responsables,
            'hitosDisponibles' => $hitosDisponibles,
            'camposPersonalizados' => $camposPersonalizados,
            'valoresCamposPersonalizados' => $valoresCamposPersonalizados,
            'categorias' => $categorias,
            'estados' => [
                ['value' => 'pendiente', 'label' => 'Pendiente'],
                ['value' => 'en_progreso', 'label' => 'En Progreso'],
                ['value' => 'completado', 'label' => 'Completado'],
            ]
        ]);
    }

    /**
     * Actualiza un hito desde el área de usuario.
     */
    public function update(Request $request, Proyecto $proyecto, Hito $hito): RedirectResponse
    {
        // Solo gestores y responsable del proyecto pueden editar hitos
        abort_unless($this->puedeGestionarHitosDelProyecto($proyecto), 403, 'Solo el responsable o gestores del proyecto pueden editar hitos');
        abort_unless($hito->proyecto_id === $proyecto->id, 404, 'El hito no pertenece a este proyecto');

        // Validar datos
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'estado' => 'nullable|in:pendiente,en_progreso,completado',
            'responsable_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:hitos,id',
            'orden' => 'nullable|integer|min:0',
            'campos_personalizados' => 'nullable|array',
            'etiquetas' => 'nullable|array',
            'etiquetas.*' => 'exists:etiquetas,id',
        ]);

        // Usar el servicio para actualizar el hito
        $result = $this->hitoService->update($hito, $validated);

        if ($result['success']) {
            return redirect()
                ->route('user.mis-proyectos.show', ['proyecto' => $proyecto->id, 'tab' => 'hitos'])
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Muestra el formulario para crear un nuevo entregable dentro de un hito.
     */
    public function createEntregable(Request $request, Proyecto $proyecto, Hito $hito): Response
    {
        // Solo gestores y responsable del proyecto pueden crear entregables
        abort_unless($this->puedeGestionarHitosDelProyecto($proyecto), 403, 'Solo el responsable o gestores del proyecto pueden crear entregables');
        abort_unless($hito->proyecto_id === $proyecto->id, 404, 'El hito no pertenece a este proyecto');

        // Obtener posibles responsables: participantes del proyecto
        $usuarios = $proyecto->participantes()
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);

        // Obtener campos personalizados para entregables
        $camposPersonalizados = $this->campoPersonalizadoRepository->getActivosParaEntregables();

        // Obtener categorías de etiquetas disponibles (filtradas para entregables)
        $categorias = CategoriaEtiqueta::with('etiquetas')
            ->activas()
            ->paraEntregables()
            ->ordenado()
            ->get();

        // Estados y prioridades disponibles
        $estados = [
            ['value' => 'pendiente', 'label' => 'Pendiente'],
            ['value' => 'en_progreso', 'label' => 'En Progreso'],
        ];

        $prioridades = [
            ['value' => 'baja', 'label' => 'Baja', 'color' => 'blue'],
            ['value' => 'media', 'label' => 'Media', 'color' => 'yellow'],
            ['value' => 'alta', 'label' => 'Alta', 'color' => 'red'],
        ];

        return Inertia::render('Modules/Proyectos/User/MisEntregables/Create', [
            'proyecto' => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
            ],
            'hito' => $hito,
            'usuarios' => $usuarios,
            'camposPersonalizados' => $camposPersonalizados,
            'categorias' => $categorias,
            'estados' => $estados,
            'prioridades' => $prioridades,
            'siguienteOrden' => ($hito->entregables()->max('orden') ?? 0) + 1,
        ]);
    }

    /**
     * Guarda un nuevo entregable en la base de datos desde el área de usuario.
     */
    public function storeEntregable(Request $request, Proyecto $proyecto, Hito $hito): RedirectResponse
    {
        // Solo gestores y responsable del proyecto pueden crear entregables
        abort_unless($this->puedeGestionarHitosDelProyecto($proyecto), 403, 'Solo el responsable o gestores del proyecto pueden crear entregables');
        abort_unless($hito->proyecto_id === $proyecto->id, 404, 'El hito no pertenece a este proyecto');

        // Validar datos
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'estado' => 'nullable|in:pendiente,en_progreso',
            'prioridad' => 'nullable|in:baja,media,alta',
            'responsable_id' => 'required|exists:users,id',
            'usuarios' => 'nullable|array',
            'usuarios.*.user_id' => 'required|exists:users,id',
            'usuarios.*.rol' => 'required|in:colaborador,revisor',
            'orden' => 'nullable|integer|min:0',
            'campos_personalizados' => 'nullable|array',
            'etiquetas' => 'nullable|array',
            'etiquetas.*' => 'exists:etiquetas,id',
        ]);

        // Añadir hito_id a los datos
        $data = array_merge($validated, [
            'hito_id' => $hito->id,
            'estado' => $validated['estado'] ?? 'pendiente',
            'prioridad' => $validated['prioridad'] ?? 'media',
        ]);

        // Usar el servicio para crear el entregable
        $result = $this->entregableService->create($data);

        // Asignar usuarios si existen
        if ($result['success'] && !empty($validated['usuarios'])) {
            $result['entregable']->asignarUsuarios($validated['usuarios']);
        }

        if ($result['success']) {
            return redirect()
                ->route('user.mis-proyectos.show', ['proyecto' => $proyecto->id, 'tab' => 'hitos'])
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }

    /**
     * Muestra el formulario para editar un entregable desde el área de usuario.
     */
    public function editEntregable(Request $request, Proyecto $proyecto, Hito $hito, Entregable $entregable): Response
    {
        // Solo gestores y responsable del proyecto pueden editar entregables
        abort_unless($this->puedeGestionarHitosDelProyecto($proyecto), 403, 'Solo el responsable o gestores del proyecto pueden editar entregables');
        abort_unless($hito->proyecto_id === $proyecto->id, 404, 'El hito no pertenece a este proyecto');
        abort_unless($entregable->hito_id === $hito->id, 404, 'El entregable no pertenece a este hito');

        $entregable->load(['responsable:id,name,email', 'usuarios:id,name,email', 'etiquetas.categoria']);

        // Obtener posibles responsables: participantes del proyecto
        $usuarios = $proyecto->participantes()
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);

        // Preparar usuarios asignados para el formulario
        $usuariosAsignados = $entregable->usuarios->map(fn($user) => [
            'user_id' => $user->id,
            'rol' => $user->pivot->rol ?? 'colaborador',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ])->values()->toArray();

        // Obtener campos personalizados y valores actuales
        $camposPersonalizados = $this->campoPersonalizadoRepository->getActivosParaEntregables();
        $valoresCamposPersonalizados = $entregable->getCamposPersonalizadosValues();

        // Obtener categorías de etiquetas disponibles (filtradas para entregables)
        $categorias = CategoriaEtiqueta::with('etiquetas')
            ->activas()
            ->paraEntregables()
            ->ordenado()
            ->get();

        // Estados y prioridades disponibles
        $estados = [
            ['value' => 'pendiente', 'label' => 'Pendiente'],
            ['value' => 'en_progreso', 'label' => 'En Progreso'],
            ['value' => 'completado', 'label' => 'Completado'],
        ];

        $prioridades = [
            ['value' => 'baja', 'label' => 'Baja', 'color' => 'blue'],
            ['value' => 'media', 'label' => 'Media', 'color' => 'yellow'],
            ['value' => 'alta', 'label' => 'Alta', 'color' => 'red'],
        ];

        return Inertia::render('Modules/Proyectos/User/MisEntregables/Edit', [
            'proyecto' => [
                'id' => $proyecto->id,
                'nombre' => $proyecto->nombre,
            ],
            'hito' => $hito,
            'entregable' => $entregable,
            'usuarios' => $usuarios,
            'usuariosAsignados' => $usuariosAsignados,
            'camposPersonalizados' => $camposPersonalizados,
            'valoresCamposPersonalizados' => $valoresCamposPersonalizados,
            'categorias' => $categorias,
            'estados' => $estados,
            'prioridades' => $prioridades,
        ]);
    }

    /**
     * Actualiza un entregable desde el área de usuario.
     */
    public function updateEntregable(Request $request, Proyecto $proyecto, Hito $hito, Entregable $entregable): RedirectResponse
    {
        // Solo gestores y responsable del proyecto pueden editar entregables
        abort_unless($this->puedeGestionarHitosDelProyecto($proyecto), 403, 'Solo el responsable o gestores del proyecto pueden editar entregables');
        abort_unless($hito->proyecto_id === $proyecto->id, 404, 'El hito no pertenece a este proyecto');
        abort_unless($entregable->hito_id === $hito->id, 404, 'El entregable no pertenece a este hito');

        // Validar datos
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'estado' => 'nullable|in:pendiente,en_progreso,completado',
            'prioridad' => 'nullable|in:baja,media,alta',
            'responsable_id' => 'required|exists:users,id',
            'usuarios' => 'nullable|array',
            'usuarios.*.user_id' => 'required|exists:users,id',
            'usuarios.*.rol' => 'required|in:colaborador,revisor',
            'orden' => 'nullable|integer|min:0',
            'campos_personalizados' => 'nullable|array',
            'etiquetas' => 'nullable|array',
            'etiquetas.*' => 'exists:etiquetas,id',
        ]);

        // Usar el servicio para actualizar el entregable
        $result = $this->entregableService->update($entregable, $validated);

        // Actualizar usuarios asignados si existen
        if ($result['success'] && isset($validated['usuarios'])) {
            $result['entregable']->asignarUsuarios($validated['usuarios']);
        }

        if ($result['success']) {
            return redirect()
                ->route('user.mis-proyectos.show', ['proyecto' => $proyecto->id, 'tab' => 'hitos'])
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
    }
}