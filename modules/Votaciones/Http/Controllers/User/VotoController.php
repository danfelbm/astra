<?php

namespace Modules\Votaciones\Http\Controllers\User;

use Modules\Core\Http\Controllers\UserController;

use Modules\Votaciones\Jobs\SendVoteConfirmationEmailJob;
use Modules\Votaciones\Jobs\SendVoteConfirmationWhatsAppJob;
use Modules\Votaciones\Models\Categoria;
use Modules\Votaciones\Models\UrnaSession;
use Modules\Votaciones\Models\Votacion;
use Modules\Votaciones\Models\Voto;
use Modules\Core\Services\IpAddressService;
use Modules\Votaciones\Services\TokenService;
use Modules\Core\Traits\HasAdvancedFilters;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class VotoController extends UserController
{
    use HasAdvancedFilters;
    /**
     * Display votaciones available for the authenticated user.
     */
    public function index(Request $request): Response
    {
        // Verificar permisos de usuario
        abort_unless(auth()->user()->can('votaciones.view_public'), 403, 'No tienes permisos para ver las votaciones disponibles');
        
        $user = Auth::user();
        $mostrarPasadas = $request->boolean('mostrar_pasadas', false);
        
        // Query base para votaciones asignadas al usuario
        $query = Votacion::with(['categoria'])
            ->withCount('votantes')
            ->whereHas('votantes', function ($q) use ($user) {
                $q->where('usuario_id', $user->id);
            });

        // Filtro base según mostrar_pasadas
        if ($mostrarPasadas) {
            // Mostrar votaciones pasadas (finalizadas)
            $query->where(function ($q) {
                $q->where('estado', 'finalizada')
                  ->orWhere('fecha_fin', '<', now());
            });
        } else {
            // CAMBIO CRÍTICO: Mostrar TODAS las votaciones (activas Y finalizadas)
            // Las finalizadas deben verse para consultar resultados y verificar tokens
            // El bloqueo para votar se hace en el método show(), NO aquí
            // Solo excluimos las que están en borrador
            $query->where('estado', '!=', 'borrador');
            // NO filtramos por fecha_fin - mostramos todas las asignadas al usuario
        }

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'titulo', 'descripcion', 'categoria_id', 'estado',
            'fecha_inicio', 'fecha_fin', 'resultados_publicos',
            'created_at'
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['titulo', 'descripcion'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);

        $votaciones = $query->orderBy($mostrarPasadas ? 'fecha_fin' : 'fecha_fin', $mostrarPasadas ? 'desc' : 'asc')
            ->paginate(10)
            ->withQueryString();

        // Agregar información de estado y acciones disponibles
        $votaciones->getCollection()->transform(function ($votacion) use ($user) {
            $now = now();
            $yaVoto = $votacion->votos()->where('usuario_id', $user->id)->exists();
            
            // Buscar sesión de urna existente para determinar estado
            $urnaSession = UrnaSession::where('votacion_id', $votacion->id)
                ->where('usuario_id', $user->id)
                ->first();
            
            // Determinar estado de la sesión de urna
            $urnaSessionStatus = null;
            if ($urnaSession) {
                if ($urnaSession->status === 'voted') {
                    $urnaSessionStatus = 'voted';
                } elseif ($urnaSession->hasExpired()) {
                    $urnaSessionStatus = 'expired';
                } else {
                    $urnaSessionStatus = 'active';
                }
            }
            
            // Determinar estado de la votación
            $esActiva = $votacion->estado === 'activa';
            $estaEnPeriodo = $now >= $votacion->fecha_inicio && $now <= $votacion->fecha_fin;
            $haFinalizado = $now > $votacion->fecha_fin || $votacion->estado === 'finalizada';
            
            $votacion->ya_voto = $yaVoto;
            $votacion->urna_session_status = $urnaSessionStatus; // Estado de la sesión de urna
            $votacion->puede_votar = $esActiva && $estaEnPeriodo && !$yaVoto;
            $votacion->ha_finalizado = $haFinalizado;
            $votacion->puede_ver_voto = $yaVoto; // Puede ver su voto si ya votó
            $votacion->resultados_visibles = $votacion->resultadosVisibles(); // Puede ver resultados si están públicos
            
            // Estado visual
            if ($haFinalizado) {
                $votacion->estado_visual = 'finalizada';
            } elseif (!$estaEnPeriodo && $now < $votacion->fecha_inicio) {
                $votacion->estado_visual = 'pendiente';
            } elseif ($esActiva && $estaEnPeriodo) {
                $votacion->estado_visual = 'activa';
            } else {
                $votacion->estado_visual = 'inactiva';
            }
            
            return $votacion;
        });

        $categorias = Categoria::activas()->get();

        return Inertia::render('Modules/Votaciones/User/Index', [
            'votaciones' => $votaciones,
            'categorias' => $categorias,
            'filters' => $request->only(['search', 'advanced_filters', 'mostrar_pasadas']),
            'mostrar_pasadas' => $mostrarPasadas,
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
            // Props de permisos de usuario
            'canVote' => auth()->user()->can('votaciones.vote'),
            'canViewResults' => auth()->user()->can('votaciones.view_results'),
            'canViewOwnVote' => auth()->user()->can('votaciones.view_own_vote'),
        ]);
    }

    /**
     * Show voting form for a specific votacion.
     */
    public function show(Votacion $votacion): Response|RedirectResponse
    {
        // Verificar permisos de votación
        abort_unless(auth()->user()->can('votaciones.vote'), 403, 'No tienes permisos para participar en votaciones');
        
        $user = Auth::user();

        // Verificar que el usuario esté asignado a esta votación
        if (!$votacion->votantes()->where('usuario_id', $user->id)->exists()) {
            return redirect()
                ->route('user.votaciones.index')
                ->with('error', 'No tienes permisos para participar en esta votación.');
        }

        // Verificar que la votación esté activa
        if ($votacion->estado !== 'activa') {
            return redirect()
                ->route('user.votaciones.index')
                ->with('error', 'Esta votación no está activa.');
        }

        // Buscar CUALQUIER sesión existente para el usuario/votación (constraint único absoluto)
        $urnaSession = UrnaSession::where('votacion_id', $votacion->id)
            ->where('usuario_id', $user->id)
            ->first();

        // Si existe sesión con status 'voted', el usuario ya participó
        if ($urnaSession && $urnaSession->status === 'voted') {
            return redirect()
                ->route('user.votaciones.mi-voto', ['votacion' => $votacion->id])
                ->with('info', 'Ya has participado en esta votación. Puedes consultar tu voto aquí.');
        }

        $now = Carbon::now();
        $sessionDuration = config('votaciones.urna_session_duration', 5);
        
        // Si no hay ninguna sesión, crear una nueva sesión active
        if (!$urnaSession) {
            // Verificar que esté en el rango de fechas válido para abrir urna
            if ($now < $votacion->fecha_inicio || $now > $votacion->fecha_fin) {
                return redirect()
                    ->route('user.votaciones.index')
                    ->with('error', 'Esta votación no está disponible en este momento.');
            }

            // Crear nueva sesión de urna
            $urnaSession = UrnaSession::create([
                'votacion_id' => $votacion->id,
                'usuario_id' => $user->id,
                'opened_at' => $now,
                'status' => 'active',
                'ip_address' => IpAddressService::getRealIp(request()),
                'user_agent' => request()->userAgent(),
                'expires_at' => $now->copy()->addMinutes($sessionDuration),
            ]);

            // Registrar apertura de urna en audit log
            $urnaSession->logAction('abrió urna', "Sesión de {$sessionDuration} minutos iniciada");
        } else {
            // Aquí sabemos que existe sesión active (el caso voted ya se manejó arriba)
            // Verificar que la sesión no haya expirado
            if ($urnaSession->hasExpired()) {
                // Registrar en audit log antes de eliminar
                $urnaSession->logAction('expiró urna', 'Sesión eliminada por expiración - usuario puede reintentar');
                
                // Eliminar la sesión expirada completamente para evitar errores de constraint
                $urnaSession->delete();
                
                return redirect()
                    ->route('user.votaciones.index')
                    ->with('error', 'Tu sesión de votación ha expirado. Por favor, intenta nuevamente.');
            }

            // Verificar consistencia de IP si está configurado
            if (config('votaciones.urna_verify_ip', true)) {
                $currentIp = IpAddressService::getRealIp(request());
                if ($urnaSession->ip_address !== $currentIp) {
                    $urnaSession->logUnauthorizedAttempt('acceso desde IP diferente', "IP original: {$urnaSession->ip_address}, IP actual: {$currentIp}");
                    return redirect()
                        ->route('user.votaciones.index')
                        ->with('error', 'Sesión inválida. Por seguridad, debes completar tu voto desde la misma conexión.');
                }
            }
        }

        $votacion->load('categoria');
        
        // Convertir fechas a la zona horaria de la votación para mostrar al usuario
        $votacionData = $votacion->toArray();
        $votacionData['fecha_inicio_local'] = Carbon::parse($votacion->fecha_inicio)
            ->setTimezone($votacion->timezone)
            ->toISOString();
        $votacionData['fecha_fin_local'] = Carbon::parse($votacion->fecha_fin)
            ->setTimezone($votacion->timezone)
            ->toISOString();
        
        // Agregar información de la sesión de urna
        $votacionData['urna_session'] = [
            'opened_at' => $urnaSession->opened_at->toISOString(),
            'expires_at' => $urnaSession->expires_at->toISOString(),
            'remaining_seconds' => $urnaSession->getRemainingSeconds(),
            'remaining_formatted' => $urnaSession->getRemainingTimeFormatted(),
            'warning_time' => config('votaciones.urna_warning_time', 2) * 60, // en segundos
            'critical_time' => config('votaciones.urna_critical_time', 1) * 60, // en segundos
        ];
        
        // Obtener candidatos elegibles para campos perfil_candidatura
        $candidatosElegibles = $this->obtenerCandidatosElegibles($votacion);

        return Inertia::render('Modules/Votaciones/User/Votar', [
            'votacion' => $votacionData,
            'candidatosElegibles' => $candidatosElegibles,
        ]);
    }

    /**
     * Store a vote for a specific votacion.
     */
    public function store(Request $request, Votacion $votacion): RedirectResponse
    {
        // Establecer tiempo límite de ejecución para evitar timeouts
        // Damos 60 segundos para procesar el voto (suficiente incluso en alta carga)
        set_time_limit(60);
        ini_set('max_execution_time', 60);
        
        // Verificar permisos de votación
        abort_unless(auth()->user()->can('votaciones.vote'), 403, 'No tienes permisos para participar en votaciones');
        
        $user = Auth::user();

        // Verificar que el usuario esté asignado a esta votación
        if (!$votacion->votantes()->where('usuario_id', $user->id)->exists()) {
            return back()->with('error', 'No tienes permisos para participar en esta votación.');
        }

        // Verificar que la votación esté activa
        if ($votacion->estado !== 'activa') {
            return back()->with('error', 'Esta votación no está activa.');
        }

        // Verificar sesión de urna activa
        $urnaSession = UrnaSession::where('votacion_id', $votacion->id)
            ->where('usuario_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$urnaSession) {
            return back()->with('error', 'No tienes una sesión de votación activa. Por favor, vuelve a intentar.');
        }

        // Verificar que la sesión no haya expirado
        if ($urnaSession->hasExpired()) {
            $urnaSession->expire();
            return back()->with('error', 'Tu sesión de votación ha expirado. Por favor, intenta nuevamente.');
        }

        // Verificar consistencia de IP si está configurado
        if (config('votaciones.urna_verify_ip', true)) {
            $currentIp = IpAddressService::getRealIp($request);
            if ($urnaSession->ip_address !== $currentIp) {
                $urnaSession->logUnauthorizedAttempt('intento de voto desde IP diferente', "IP original: {$urnaSession->ip_address}, IP actual: {$currentIp}");
                return back()->with('error', 'Sesión inválida. Por seguridad, debes completar tu voto desde la misma conexión.');
            }
        }

        $now = Carbon::now();
        
        // Verificar si el usuario entró antes del cierre PERO tiene sesión activa
        // Esto permite que alguien que entró a las 4:59 PM pueda votar hasta las 5:04 PM si la votación cierra a las 5:00 PM
        $puedeVotarPorSesion = $urnaSession->opened_at <= $votacion->fecha_fin && $urnaSession->isActive();
        
        if (!$puedeVotarPorSesion) {
            return back()->with('error', 'Esta votación no está disponible en este momento.');
        }

        // Verificar que el usuario no haya votado ya
        if ($votacion->votos()->where('usuario_id', $user->id)->exists()) {
            return back()->with('error', 'Ya has participado en esta votación.');
        }

        // Validar las respuestas del formulario
        $formConfig = $votacion->formulario_config;
        $validationRules = [];
        $validationMessages = [];

        foreach ($formConfig as $field) {
            $fieldName = "respuestas.{$field['id']}";
            
            if ($field['required']) {
                // Para campos de tipo convocatoria, usar 'present' en lugar de 'required'
                // Esto permite null (voto en blanco) pero requiere que el campo esté presente
                if ($field['type'] === 'convocatoria') {
                    $validationRules[$fieldName] = 'present';
                    $validationMessages[$fieldName . '.present'] = "El campo '{$field['title']}' es obligatorio.";
                } else {
                    $validationRules[$fieldName] = 'required';
                    $validationMessages[$fieldName . '.required'] = "El campo '{$field['title']}' es obligatorio.";
                }
            }

            // Validaciones específicas por tipo de campo
            if ($field['type'] === 'checkbox' && !$field['required']) {
                $validationRules[$fieldName] = 'array';
            }
        }

        $validator = Validator::make($request->all(), $validationRules, $validationMessages);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Obtener las respuestas
            $respuestas = $request->input('respuestas', []);
            
            // PROCESAMIENTO SÍNCRONO CON LOCK PESIMISTA PARA GARANTIZAR INTEGRIDAD
            $voto = null;
            
            // Usar lock distribuido para prevenir race conditions en múltiples servidores
            $lockKey = "vote_lock_{$votacion->id}_{$user->id}";
            $lock = Cache::lock($lockKey, 10); // Lock por 10 segundos máximo
            
            if (!$lock->get()) {
                // Otro proceso está procesando el voto, verificar si ya existe
                sleep(1); // Esperar un momento
                $votoExistente = Voto::where('votacion_id', $votacion->id)
                    ->where('usuario_id', $user->id)
                    ->first();
                    
                if ($votoExistente) {
                    return redirect()
                        ->route('user.votaciones.mi-voto', ['votacion' => $votacion->id])
                        ->with('success', 'Tu voto ha sido registrado exitosamente.');
                }
                
                return back()->with('error', 'Tu voto está siendo procesado. Por favor, espera un momento.');
            }
            
            try {
                // Procesar el voto dentro de una transacción con locks pesimistas
                DB::transaction(function() use ($votacion, $user, $respuestas, $request, $urnaSession, &$voto) {
                    // Lock pesimista en la votación para prevenir modificaciones concurrentes
                    $votacionLocked = Votacion::where('id', $votacion->id)
                        ->lockForUpdate()
                        ->first();
                    
                    // Verificar DENTRO de la transacción si ya votó (con lock)
                    $votoExistente = Voto::where('votacion_id', $votacion->id)
                        ->where('usuario_id', $user->id)
                        ->lockForUpdate()
                        ->first();
                    
                    if ($votoExistente) {
                        // Ya votó, cerrar sesión y usar el voto existente
                        if ($urnaSession->status === 'active') {
                            $urnaSession->closeByVote();
                        }
                        $voto = $votoExistente;
                        
                        \Log::info('Voto ya existente encontrado en transacción', [
                            'voto_id' => $votoExistente->id,
                            'user_id' => $user->id,
                            'votacion_id' => $votacion->id
                        ]);
                        
                        return;
                    }
                    
                    // Generar token único con información de la sesión de urna
                    $voteTimestamp = now();
                    $token = TokenService::generateSignedToken(
                        $votacion->id,
                        $respuestas,
                        $voteTimestamp->toISOString(),
                        $urnaSession->opened_at->toISOString()
                    );
                    
                    // Crear el voto
                    $voto = Voto::create([
                        'votacion_id' => $votacion->id,
                        'usuario_id' => $user->id,
                        'token_unico' => $token,
                        'urna_opened_at' => $urnaSession->opened_at,
                        'respuestas' => $respuestas,
                        'ip_address' => IpAddressService::getRealIp($request),
                        'user_agent' => $request->userAgent(),
                    ]);
                    
                    // Cerrar la sesión de urna inmediatamente después de guardar el voto
                    $urnaSession->closeByVote();
                    
                    \Log::info('Voto procesado exitosamente de forma síncrona', [
                        'voto_id' => $voto->id,
                        'user_id' => $user->id,
                        'votacion_id' => $votacion->id,
                        'urna_session_id' => $urnaSession->id,
                        'token_first_50' => substr($token, 0, 50)
                    ]);
                });
                
            } finally {
                // Siempre liberar el lock
                $lock->release();
            }
            
            // Despachar notificaciones DESPUÉS de la transacción exitosa
            if ($voto) {
                // Cargar relaciones necesarias para las notificaciones
                $votacion->load('categoria');
                
                // Enviar notificaciones de forma asíncrona (no crítico para el flujo)
                if (!empty($user->email)) {
                    SendVoteConfirmationEmailJob::dispatch($user, $votacion, $voto);
                }
                
                if (!empty($user->telefono)) {
                    SendVoteConfirmationWhatsAppJob::dispatch($user, $votacion, $voto);
                }
                
                // Redirigir al usuario a ver su voto confirmado
                return redirect()
                    ->route('user.votaciones.mi-voto', ['votacion' => $votacion->id])
                    ->with('success', 'Tu voto ha sido registrado exitosamente. Se ha enviado una confirmación a tu correo electrónico.');
            }
            
            // Si llegamos aquí, algo salió mal
            return back()->with('error', 'No se pudo procesar tu voto. Por favor, inténtalo de nuevo.');

        } catch (\Illuminate\Database\QueryException $e) {
            // Manejar error de constraint único (por si acaso)
            if ($e->getCode() === '23000') {
                // Verificar si el voto existe
                $votoExistente = Voto::where('votacion_id', $votacion->id)
                    ->where('usuario_id', $user->id)
                    ->first();
                
                if ($votoExistente) {
                    // Cerrar sesión de urna si está activa
                    if ($urnaSession->status === 'active') {
                        $urnaSession->closeByVote();
                    }
                    
                    return redirect()
                        ->route('user.votaciones.mi-voto', ['votacion' => $votacion->id])
                        ->with('success', 'Tu voto ha sido registrado exitosamente.');
                }
            }
            
            \Log::error('Error de base de datos al procesar voto', [
                'user_id' => $user->id,
                'votacion_id' => $votacion->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Ocurrió un error al procesar tu voto. Por favor, inténtalo de nuevo.');
            
        } catch (\Exception $e) {
            // Log del error general
            \Log::error('Error al procesar voto de forma síncrona', [
                'user_id' => $user->id,
                'votacion_id' => $votacion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Ocurrió un error al procesar tu voto. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Show the user's vote for a specific votacion.
     */
    public function miVoto(Votacion $votacion): Response|RedirectResponse
    {
        // Verificar permisos para ver voto propio
        abort_unless(auth()->user()->can('votaciones.view_own_vote'), 403, 'No tienes permisos para ver tu voto');
        
        $user = Auth::user();

        // Verificar que el usuario esté asignado a esta votación
        if (!$votacion->votantes()->where('usuario_id', $user->id)->exists()) {
            return redirect()
                ->route('user.votaciones.index')
                ->with('error', 'No tienes permisos para ver esta votación.');
        }

        // Buscar el voto del usuario
        $voto = $votacion->votos()->where('usuario_id', $user->id)->first();

        if (!$voto) {
            return redirect()
                ->route('user.votaciones.index')
                ->with('error', 'No has participado en esta votación.');
        }

        $votacion->load('categoria');

        return Inertia::render('Modules/Votaciones/User/MiVoto', [
            'votacion' => $votacion,
            'voto' => [
                'id' => $voto->id,
                'token_unico' => $voto->token_unico,
                'respuestas' => $voto->respuestas,
                'created_at' => $voto->created_at->format('Y-m-d H:i:s'),
                'ip_address' => $voto->ip_address,
            ],
        ]);
    }

    /**
     * Check urna session status
     */
    public function checkUrnaSession(Votacion $votacion): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        
        // Buscar cualquier sesión existente (constraint único absoluto)
        $urnaSession = UrnaSession::where('votacion_id', $votacion->id)
            ->where('usuario_id', $user->id)
            ->first();
        
        if (!$urnaSession) {
            return response()->json([
                'active' => false,
                'message' => 'No hay sesión activa',
            ]);
        }
        
        // Si la sesión es voted, el usuario ya participó
        if ($urnaSession->status === 'voted') {
            return response()->json([
                'active' => false,
                'voted' => true,
                'message' => 'Ya has participado en esta votación',
            ]);
        }
        
        // Verificar si ha expirado
        if ($urnaSession->hasExpired()) {
            $urnaSession->expire();
            return response()->json([
                'active' => false,
                'expired' => true,
                'message' => 'La sesión ha expirado',
            ]);
        }
        
        return response()->json([
            'active' => true,
            'opened_at' => $urnaSession->opened_at->toISOString(),
            'expires_at' => $urnaSession->expires_at->toISOString(),
            'remaining_seconds' => $urnaSession->getRemainingSeconds(),
            'remaining_formatted' => $urnaSession->getRemainingTimeFormatted(),
            'warning_time' => config('votaciones.urna_warning_time', 2) * 60,
            'critical_time' => config('votaciones.urna_critical_time', 1) * 60,
        ]);
    }

    /**
     * Obtener configuración de campos para filtros avanzados
     */
    public function getFilterFieldsConfig(): array
    {
        // Cargar categorías para el select
        $categorias = Categoria::activas()->get()->map(fn($c) => [
            'value' => $c->id,
            'label' => $c->nombre
        ]);
        
        return [
            [
                'name' => 'titulo',
                'label' => 'Título',
                'type' => 'text',
            ],
            [
                'name' => 'descripcion',
                'label' => 'Descripción',
                'type' => 'text',
            ],
            [
                'name' => 'categoria_id',
                'label' => 'Categoría',
                'type' => 'select',
                'options' => $categorias->toArray(),
            ],
            [
                'name' => 'estado',
                'label' => 'Estado de la Votación',
                'type' => 'select',
                'options' => [
                    ['value' => 'activa', 'label' => 'Activa'],
                    ['value' => 'finalizada', 'label' => 'Finalizada'],
                ],
            ],
            [
                'name' => 'fecha_inicio',
                'label' => 'Fecha de Apertura',
                'type' => 'datetime',
            ],
            [
                'name' => 'fecha_fin',
                'label' => 'Fecha Límite',
                'type' => 'datetime',
            ],
            [
                'name' => 'resultados_publicos',
                'label' => 'Tiene Resultados Públicos',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Sí'],
                    ['value' => 0, 'label' => 'No'],
                ],
            ],
        ];
    }
    

    /**
     * Obtener candidatos elegibles para campos convocatoria y perfil_candidatura (deprecated)
     */
    private function obtenerCandidatosElegibles(Votacion $votacion): array
    {
        $candidatosPorCampo = [];
        
        // Revisar cada campo del formulario
        foreach ($votacion->formulario_config as $campo) {
            // Procesar campos de tipo convocatoria (nuevo)
            if ($campo['type'] === 'convocatoria') {
                // Obtener la configuración
                $config = $campo['convocatoriaConfig'] ?? [];
                
                // Si no hay convocatoria_id configurado, continuar
                if (empty($config['convocatoria_id'])) {
                    $candidatosPorCampo[$campo['id']] = [];
                    continue;
                }
                
                // Obtener información de la convocatoria
                $convocatoria = \Modules\Elecciones\Models\Convocatoria::find($config['convocatoria_id']);
                if (!$convocatoria) {
                    $candidatosPorCampo[$campo['id']] = [];
                    continue;
                }
                
                // Obtener configuración de orden (por defecto aleatorio)
                $ordenCandidatos = $config['ordenCandidatos'] ?? 'aleatorio';
                
                // Construir query base
                $query = \Modules\Core\Models\User::query()
                    ->whereHas('postulaciones', function ($q) use ($config) {
                        $q->where('estado', 'aceptada')
                          ->where('convocatoria_id', $config['convocatoria_id']);
                    })
                    ->with([
                        'postulaciones' => function ($q) use ($config) {
                            $q->where('estado', 'aceptada')
                              ->where('convocatoria_id', $config['convocatoria_id'])
                              ->with('convocatoria.cargo');
                        }
                    ]);
                
                // Aplicar ordenamiento según configuración
                switch ($ordenCandidatos) {
                    case 'alfabetico':
                        // Orden alfabético por nombre
                        $query->orderBy('name', 'asc');
                        break;
                    
                    case 'fecha_postulacion':
                        // Orden por fecha de postulación (más recientes primero)
                        // Necesitamos hacer join con postulaciones para obtener la fecha
                        $query->select('users.*')
                              ->join('postulaciones', function($join) use ($config) {
                                  $join->on('users.id', '=', 'postulaciones.user_id')
                                       ->where('postulaciones.convocatoria_id', '=', $config['convocatoria_id'])
                                       ->where('postulaciones.estado', '=', 'aceptada');
                              })
                              ->orderBy('postulaciones.created_at', 'desc');
                        break;
                    
                    case 'aleatorio':
                    default:
                        // Orden aleatorio para equidad electoral
                        $query->inRandomOrder();
                        break;
                }
                
                // Obtener usuarios con orden aplicado
                $candidatos = $query->get()
                    ->map(function ($user) use ($convocatoria) {
                        // Obtener la postulación específica a esta convocatoria
                        $postulacion = $user->postulaciones->first();
                        
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'avatar_url' => $user->avatar_url, // Incluir URL del avatar (personalizado o UI Avatars)
                            'postulacion_id' => $postulacion ? $postulacion->id : null,
                            'fecha_postulacion' => $postulacion ? $postulacion->created_at->format('Y-m-d') : null,
                            'candidatura_snapshot' => $postulacion ? $postulacion->candidatura_snapshot : null,
                            'cargo' => $convocatoria->cargo ? $convocatoria->cargo->getRutaJerarquica() : null,
                            // Información geográfica del usuario
                            'territorio' => $user->territorio ? $user->territorio->nombre : null,
                            'departamento' => $user->departamento ? $user->departamento->nombre : null,
                            'municipio' => $user->municipio ? $user->municipio->nombre : null,
                            'localidad' => $user->localidad ? $user->localidad->nombre : null,
                        ];
                    })->toArray();
                
                // Guardar candidatos para este campo junto con información de la convocatoria
                $candidatosPorCampo[$campo['id']] = [
                    'convocatoria' => [
                        'id' => $convocatoria->id,
                        'nombre' => $convocatoria->nombre,
                        'cargo' => $convocatoria->cargo ? $convocatoria->cargo->nombre : null,
                        'periodo' => $convocatoria->periodoElectoral ? $convocatoria->periodoElectoral->nombre : null,
                    ],
                    'candidatos' => $candidatos,
                ];
            }
            // Mantener soporte para perfil_candidatura (deprecated)
            else if ($campo['type'] === 'perfil_candidatura') {
                // Mantener lógica anterior para compatibilidad
                $config = $campo['perfilCandidaturaConfig'] ?? [];
                
                // Construir query base: usuarios con postulaciones aceptadas
                $query = \Modules\Core\Models\User::query()
                    ->whereHas('postulaciones', function ($q) use ($config) {
                        $q->where('estado', 'aceptada');
                        
                        // Filtrar por convocatoria según cargo y período
                        $q->whereHas('convocatoria', function ($convQ) use ($config) {
                            // Filtro por cargo - CORREGIDO: NO aplicar si es 'all'
                            if (!empty($config['cargo_id']) && $config['cargo_id'] !== 'all') {
                                $convQ->where('cargo_id', $config['cargo_id']);
                            }
                            
                            // Filtro por período electoral - CORREGIDO: NO aplicar si es 'all'
                            if (!empty($config['periodo_electoral_id']) && $config['periodo_electoral_id'] !== 'all') {
                                $convQ->where('periodo_electoral_id', $config['periodo_electoral_id']);
                            }
                            
                            // Filtros geográficos de la convocatoria
                            if (!empty($config['territorio_id'])) {
                                $convQ->where('territorio_id', $config['territorio_id']);
                            }
                            if (!empty($config['departamento_id'])) {
                                $convQ->where('departamento_id', $config['departamento_id']);
                            }
                            if (!empty($config['municipio_id'])) {
                                $convQ->where('municipio_id', $config['municipio_id']);
                            }
                            if (!empty($config['localidad_id'])) {
                                $convQ->where('localidad_id', $config['localidad_id']);
                            }
                        });
                    });
                
                // Obtener usuarios con información adicional
                $candidatos = $query->with([
                    'postulaciones' => function ($q) {
                        $q->where('estado', 'aceptada')
                          ->with('convocatoria.cargo');
                    }
                ])->get()->map(function ($user) {
                    // Obtener el cargo de la primera postulación aceptada
                    $postulacion = $user->postulaciones->first();
                    $cargo = $postulacion && $postulacion->convocatoria && $postulacion->convocatoria->cargo 
                        ? $postulacion->convocatoria->cargo->getRutaJerarquica() 
                        : null;
                    
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'cargo' => $cargo,
                        // Agregar información geográfica si está disponible
                        'territorio' => $user->territorio ? $user->territorio->nombre : null,
                        'departamento' => $user->departamento ? $user->departamento->nombre : null,
                        'municipio' => $user->municipio ? $user->municipio->nombre : null,
                        'localidad' => $user->localidad ? $user->localidad->nombre : null,
                    ];
                })->toArray();
                
                // Guardar candidatos para este campo (formato legacy)
                $candidatosPorCampo[$campo['id']] = $candidatos;
            }
        }
        
        return $candidatosPorCampo;
    }

    /**
     * Reiniciar sesión de urna expirada
     * Borra la sesión expirada y crea una nueva activa automáticamente
     */
    public function resetUrna(Votacion $votacion): RedirectResponse
    {
        $user = auth()->user();
        
        // Verificar permisos
        abort_unless($user->can('votaciones.vote'), 403, 'No tienes permisos para participar en votaciones');
        
        // Verificar que la votación esté activa
        if ($votacion->estado !== 'activa') {
            return redirect()
                ->route('user.votaciones.index')
                ->with('error', 'Esta votación no está activa.');
        }
        
        // Buscar sesión existente
        $urnaSession = UrnaSession::where('votacion_id', $votacion->id)
            ->where('usuario_id', $user->id)
            ->first();
        
        // Si existe sesión con status 'voted', el usuario ya participó
        if ($urnaSession && $urnaSession->status === 'voted') {
            return redirect()
                ->route('user.votaciones.mi-voto', ['votacion' => $votacion->id])
                ->with('info', 'Ya has participado en esta votación.');
        }
        
        // Si existe sesión y está expirada, eliminarla
        if ($urnaSession && ($urnaSession->hasExpired() || $urnaSession->status === 'active')) {
            // Registrar en audit log antes de eliminar
            $urnaSession->logAction('reinició urna', 'Sesión reiniciada por usuario - sesión anterior eliminada');
            $urnaSession->delete();
        }
        
        // Verificar que esté en el rango de fechas válido
        $now = Carbon::now();
        if ($now < $votacion->fecha_inicio || $now > $votacion->fecha_fin) {
            return redirect()
                ->route('user.votaciones.index')
                ->with('error', 'Esta votación no está disponible en este momento.');
        }
        
        // Crear nueva sesión activa automáticamente
        $sessionDuration = config('votaciones.urna_session_duration', 5);
        
        try {
            $newUrnaSession = UrnaSession::create([
                'votacion_id' => $votacion->id,
                'usuario_id' => $user->id,
                'opened_at' => $now,
                'status' => 'active',
                'ip_address' => IpAddressService::getRealIp(request()),
                'user_agent' => request()->userAgent(),
                'expires_at' => $now->copy()->addMinutes($sessionDuration),
            ]);
            
            // Registrar apertura de urna en audit log
            $newUrnaSession->logAction('abrió urna', "Sesión reiniciada - {$sessionDuration} minutos iniciados");
            
            // Redirigir directamente al formulario de votación
            return redirect()
                ->route('user.votaciones.votar', ['votacion' => $votacion->id])
                ->with('success', 'Urna reiniciada exitosamente. Tienes ' . $sessionDuration . ' minutos para votar.');
                
        } catch (\Exception $e) {
            \Log::error('Error al reiniciar urna', [
                'user_id' => $user->id,
                'votacion_id' => $votacion->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()
                ->route('user.votaciones.index')
                ->with('error', 'Ocurrió un error al reiniciar la urna. Por favor, inténtalo de nuevo.');
        }
    }
}