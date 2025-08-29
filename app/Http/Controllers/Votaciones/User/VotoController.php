<?php

namespace App\Http\Controllers\Votaciones\User;

use App\Http\Controllers\Core\UserController;


use App\Jobs\Votaciones\ProcessVoteJob;
use App\Jobs\Votaciones\SendVoteConfirmationEmailJob;
use App\Jobs\Votaciones\SendVoteConfirmationWhatsAppJob;
use App\Models\Votaciones\Categoria;
use App\Models\Votaciones\Votacion;
use App\Models\Votaciones\Voto;
use App\Services\Core\IpAddressService;
use App\Services\Votaciones\TokenService;
use App\Traits\HasAdvancedFilters;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            // Mostrar solo votaciones activas (incluyendo las próximas a abrir) que no hayan terminado
            // Excluir votaciones en borrador - solo mostrar las activas
            $query->where('estado', 'activa')
                  ->where('fecha_fin', '>=', now());
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
            
            // Verificar si hay un voto en procesamiento
            $cacheKey = "vote_status_{$votacion->id}_{$user->id}";
            $voteStatus = Cache::get($cacheKey, null);
            $processingVote = in_array($voteStatus, ['pending', 'processing']);
            
            // Determinar estado de la votación
            $esActiva = $votacion->estado === 'activa';
            $estaEnPeriodo = $now >= $votacion->fecha_inicio && $now <= $votacion->fecha_fin;
            $haFinalizado = $now > $votacion->fecha_fin || $votacion->estado === 'finalizada';
            
            $votacion->ya_voto = $yaVoto;
            $votacion->vote_processing = $processingVote; // Indicador de voto en procesamiento
            $votacion->vote_status = $voteStatus; // Estado específico del voto
            $votacion->puede_votar = $esActiva && $estaEnPeriodo && !$yaVoto && !$processingVote;
            $votacion->ha_finalizado = $haFinalizado;
            $votacion->puede_ver_voto = $yaVoto && !$processingVote; // Puede ver su voto si votó y no está procesando
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

        // Verificar si hay algún voto en procesamiento para activar polling
        $hasProcessingVotes = $votaciones->getCollection()->contains(function ($votacion) {
            return $votacion->vote_processing;
        });

        return Inertia::render('User/Votaciones/Index', [
            'votaciones' => $votaciones,
            'categorias' => $categorias,
            'filters' => $request->only(['search', 'advanced_filters', 'mostrar_pasadas']),
            'mostrar_pasadas' => $mostrarPasadas,
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
            'hasProcessingVotes' => $hasProcessingVotes, // Indicador para activar polling
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

        // Verificar que esté en el rango de fechas válido
        $now = now();
        if ($now < $votacion->fecha_inicio || $now > $votacion->fecha_fin) {
            return redirect()
                ->route('user.votaciones.index')
                ->with('error', 'Esta votación no está disponible en este momento.');
        }

        // Verificar que el usuario no haya votado ya
        if ($votacion->votos()->where('usuario_id', $user->id)->exists()) {
            return redirect()
                ->route('user.votaciones.index')
                ->with('error', 'Ya has participado en esta votación.');
        }

        $votacion->load('categoria');
        
        // Convertir fechas a la zona horaria de la votación para mostrar al usuario
        // Esto asegura que el usuario vea las fechas en la zona horaria configurada
        $votacionData = $votacion->toArray();
        $votacionData['fecha_inicio_local'] = Carbon::parse($votacion->fecha_inicio)
            ->setTimezone($votacion->timezone)
            ->toISOString();
        $votacionData['fecha_fin_local'] = Carbon::parse($votacion->fecha_fin)
            ->setTimezone($votacion->timezone)
            ->toISOString();
        
        // Obtener candidatos elegibles para campos perfil_candidatura
        $candidatosElegibles = $this->obtenerCandidatosElegibles($votacion);

        return Inertia::render('User/Votaciones/Votar', [
            'votacion' => $votacionData,
            'candidatosElegibles' => $candidatosElegibles,
        ]);
    }

    /**
     * Store a vote for a specific votacion.
     */
    public function store(Request $request, Votacion $votacion): RedirectResponse
    {
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

        // Verificar que esté en el rango de fechas válido
        $now = now();
        if ($now < $votacion->fecha_inicio || $now > $votacion->fecha_fin) {
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
            
            // Marcar en cache que el voto está pendiente
            $cacheKey = "vote_status_{$votacion->id}_{$user->id}";
            Cache::put($cacheKey, 'pending', 120);
            
            // Despachar el job para procesar el voto de forma asíncrona
            ProcessVoteJob::dispatch(
                $votacion,
                $user,
                $respuestas,
                IpAddressService::getRealIp($request),
                $request->userAgent()
            );
            
            // Redirigir inmediatamente con un mensaje informativo
            // El frontend mostrará un loading y hará polling al estado
            return redirect()
                ->route('user.votaciones.index')
                ->with('info', 'Tu voto está siendo firmado digitalmente. Te notificaremos cuando esté listo.')
                ->with('processing_vote', [
                    'votacion_id' => $votacion->id,
                    'check_status_url' => route('user.votaciones.check-status', $votacion->id)
                ]);

        } catch (\Exception $e) {
            // Log del error
            \Log::error('Error al despachar job de voto', [
                'user_id' => $user->id,
                'votacion_id' => $votacion->id,
                'error' => $e->getMessage()
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

        return Inertia::render('User/Votaciones/MiVoto', [
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
                $convocatoria = \App\Models\Elecciones\Convocatoria::find($config['convocatoria_id']);
                if (!$convocatoria) {
                    $candidatosPorCampo[$campo['id']] = [];
                    continue;
                }
                
                // Obtener configuración de orden (por defecto aleatorio)
                $ordenCandidatos = $config['ordenCandidatos'] ?? 'aleatorio';
                
                // Construir query base
                $query = \App\Models\Core\User::query()
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
                $query = \App\Models\Core\User::query()
                    ->whereHas('postulaciones', function ($q) use ($config) {
                        $q->where('estado', 'aceptada');
                        
                        // Filtrar por convocatoria según cargo y período
                        $q->whereHas('convocatoria', function ($convQ) use ($config) {
                            // Filtro por cargo
                            if (!empty($config['cargo_id'])) {
                                $convQ->where('cargo_id', $config['cargo_id']);
                            }
                            
                            // Filtro por período electoral
                            if (!empty($config['periodo_electoral_id'])) {
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
}