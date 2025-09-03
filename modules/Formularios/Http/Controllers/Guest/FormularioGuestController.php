<?php

namespace Modules\Formularios\Http\Controllers\Guest;

use Modules\Core\Http\Controllers\GuestController;
use Modules\Formularios\Models\Formulario;
use Modules\Formularios\Models\FormularioRespuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class FormularioGuestController extends GuestController
{
    /**
     * Mostrar formulario público
     */
    public function show($slug): Response
    {
        $formulario = Formulario::where('slug', $slug)
            ->with(['categoria'])
            ->firstOrFail();
        
        // Verificar si el formulario está disponible
        if (!$formulario->estaDisponible()) {
            return Inertia::render('Modules/Formularios/Guest/NoDisponible', [
                'mensaje' => $this->getMensajeNoDisponible($formulario),
            ]);
        }
        
        // Verificar si el usuario puede llenar el formulario
        $usuario = Auth::user();
        if (!$formulario->puedeSerLlenadoPor($usuario)) {
            if (!$usuario && !$formulario->permite_visitantes) {
                // Redirigir a login si no está autenticado y no permite visitantes
                return redirect()->route('login')
                    ->with('message', 'Debes iniciar sesión para llenar este formulario.');
            }
            
            return Inertia::render('Modules/Formularios/Guest/SinAcceso', [
                'mensaje' => 'No tienes permisos para llenar este formulario.',
            ]);
        }
        
        // Buscar respuesta en borrador si existe
        $respuestaBorrador = null;
        if ($usuario) {
            $respuestaBorrador = FormularioRespuesta::where('formulario_id', $formulario->id)
                ->where('usuario_id', $usuario->id)
                ->where('estado', 'borrador')
                ->first();
        }
        
        // Verificar si ya respondió este formulario
        $yaRespondido = false;
        if ($usuario) {
            $yaRespondido = FormularioRespuesta::where('formulario_id', $formulario->id)
                ->where('usuario_id', $usuario->id)
                ->where('estado', 'enviado')
                ->exists();
                
            if ($yaRespondido && $formulario->limite_por_usuario <= 1) {
                return Inertia::render('Modules/Formularios/Guest/YaRespondido', [
                    'mensaje' => 'Ya has respondido este formulario.',
                ]);
            }
        }
        
        // Obtener candidatos elegibles para campos de tipo convocatoria y perfil_candidatura
        $candidatosElegibles = $this->obtenerCandidatosElegibles($formulario);
        
        return Inertia::render('Modules/Formularios/Guest/Show', [
            'formulario' => [
                'id' => $formulario->id,
                'titulo' => $formulario->titulo,
                'descripcion' => $formulario->descripcion,
                'slug' => $formulario->slug,
                'configuracion_campos' => $formulario->configuracion_campos,
                'configuracion_general' => $formulario->configuracion_general,
                // Temporalmente desactivado hasta implementar recaptcha
                'requiere_captcha' => false, // $formulario->requiere_captcha && !$usuario,
                'categoria' => $formulario->categoria,
                'mensaje_confirmacion' => $formulario->mensaje_confirmacion,
            ],
            'respuestaBorrador' => $respuestaBorrador ? [
                'id' => $respuestaBorrador->id,
                'respuestas' => $respuestaBorrador->respuestas,
            ] : null,
            'esVisitante' => !$usuario,
            'candidatosElegibles' => $candidatosElegibles,
        ]);
    }
    
    /**
     * Procesar y guardar la respuesta del formulario
     */
    public function store(Request $request, $slug)
    {
        $formulario = Formulario::where('slug', $slug)->firstOrFail();
        
        // Verificar disponibilidad
        if (!$formulario->estaDisponible()) {
            return redirect()->back()
                ->with('error', 'Este formulario ya no está disponible.');
        }
        
        // Verificar permisos
        $usuario = Auth::user();
        if (!$formulario->puedeSerLlenadoPor($usuario)) {
            return redirect()->back()
                ->with('error', 'No tienes permisos para llenar este formulario.');
        }
        
        // Validación básica
        $rules = [
            'respuestas' => 'required|array',
        ];
        
        // Si es visitante, validar campos adicionales
        if (!$usuario) {
            $rules['nombre_visitante'] = 'required|string|max:255';
            $rules['email_visitante'] = 'required|email|max:255';
            $rules['telefono_visitante'] = 'nullable|string|max:20';
            $rules['documento_visitante'] = 'nullable|string|max:50';
            
            // Temporalmente desactivado hasta implementar recaptcha
            // if ($formulario->requiere_captcha) {
            //     $rules['captcha'] = 'required'; // Implementar validación de captcha
            // }
        }
        
        // Validar campos del formulario dinámicamente
        foreach ($formulario->configuracion_campos as $campo) {
            if (isset($campo['required']) && $campo['required']) {
                $rules["respuestas.{$campo['id']}"] = 'required';
            }
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        DB::beginTransaction();
        try {
            // Verificar si ya existe una respuesta para actualizar
            if ($usuario) {
                $respuestaExistente = FormularioRespuesta::where('formulario_id', $formulario->id)
                    ->where('usuario_id', $usuario->id)
                    ->where('estado', 'borrador')
                    ->first();
                    
                if ($respuestaExistente) {
                    // Actualizar respuesta existente
                    $respuestaExistente->update([
                        'respuestas' => $request->respuestas,
                        'estado' => 'enviado',
                        'enviado_en' => now(),
                        'metadata' => [
                            'ip' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ],
                    ]);
                    
                    $respuesta = $respuestaExistente;
                } else {
                    // Crear nueva respuesta
                    $respuesta = FormularioRespuesta::create([
                        'formulario_id' => $formulario->id,
                        'usuario_id' => $usuario->id,
                        'respuestas' => $request->respuestas,
                        'estado' => 'enviado',
                        'iniciado_en' => now(),
                        'enviado_en' => now(),
                        'metadata' => [
                            'ip' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ],
                    ]);
                }
            } else {
                // Crear respuesta de visitante
                $respuesta = FormularioRespuesta::create([
                    'formulario_id' => $formulario->id,
                    'nombre_visitante' => $request->nombre_visitante,
                    'email_visitante' => $request->email_visitante,
                    'telefono_visitante' => $request->telefono_visitante,
                    'documento_visitante' => $request->documento_visitante,
                    'respuestas' => $request->respuestas,
                    'estado' => 'enviado',
                    'iniciado_en' => now(),
                    'enviado_en' => now(),
                    'metadata' => [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ],
                ]);
            }
            
            DB::commit();
            
            // Enviar notificaciones si están configuradas
            $this->enviarNotificaciones($formulario, $respuesta);
            
            return redirect()->route('formularios.success', $formulario->slug)
                ->with('codigo_confirmacion', $respuesta->codigo_confirmacion);
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error al enviar el formulario. Por favor intenta nuevamente.')
                ->withInput();
        }
    }
    
    /**
     * Mostrar página de éxito
     */
    public function success($slug): Response
    {
        $formulario = Formulario::where('slug', $slug)->firstOrFail();
        
        $codigoConfirmacion = session('codigo_confirmacion');
        
        if (!$codigoConfirmacion) {
            return redirect()->route('formularios.show', $slug);
        }
        
        return Inertia::render('Modules/Formularios/Guest/Success', [
            'formulario' => [
                'titulo' => $formulario->titulo,
                'mensaje_confirmacion' => $formulario->mensaje_confirmacion,
            ],
            'codigoConfirmacion' => $codigoConfirmacion,
        ]);
    }
    
    /**
     * Obtener mensaje de no disponible según el estado
     */
    private function getMensajeNoDisponible(Formulario $formulario): string
    {
        $estado = $formulario->getEstadoTemporal();
        
        return match($estado) {
            'borrador' => 'Este formulario aún no está publicado.',
            'archivado' => 'Este formulario ha sido archivado.',
            'inactivo' => 'Este formulario no está activo.',
            'programado' => 'Este formulario estará disponible a partir del ' . $formulario->fecha_inicio->format('d/m/Y H:i'),
            'finalizado' => 'Este formulario finalizó el ' . $formulario->fecha_fin->format('d/m/Y H:i'),
            'lleno' => 'Este formulario ha alcanzado el límite máximo de respuestas.',
            default => 'Este formulario no está disponible.',
        };
    }
    
    /**
     * Enviar notificaciones configuradas
     */
    private function enviarNotificaciones(Formulario $formulario, FormularioRespuesta $respuesta): void
    {
        // Implementar envío de emails si están configurados
        if ($formulario->emails_notificacion && count($formulario->emails_notificacion) > 0) {
            // TODO: Implementar queue de emails
            // Mail::to($formulario->emails_notificacion)->queue(new NuevaRespuestaFormulario($formulario, $respuesta));
        }
        
        // Enviar confirmación al respondiente si tiene email
        if ($respuesta->email_visitante) {
            // TODO: Implementar email de confirmación
            // Mail::to($respuesta->email_visitante)->queue(new ConfirmacionFormulario($formulario, $respuesta));
        }
    }
    
    /**
     * Obtener candidatos elegibles para campos de tipo convocatoria y perfil_candidatura
     */
    private function obtenerCandidatosElegibles(Formulario $formulario): array
    {
        $candidatosPorCampo = [];
        
        // Revisar cada campo del formulario
        foreach ($formulario->configuracion_campos as $campo) {
            // Procesar campos de tipo convocatoria
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
                            'avatar_url' => $user->avatar_url,
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
            // Soporte para perfil_candidatura (deprecated pero necesario para compatibilidad)
            else if ($campo['type'] === 'perfil_candidatura') {
                $config = $campo['perfilCandidaturaConfig'] ?? [];
                
                // Construir query base: usuarios con postulaciones aceptadas
                $query = \Modules\Core\Models\User::query()
                    ->whereHas('postulaciones', function ($q) use ($config) {
                        $q->where('estado', 'aceptada');
                        
                        // Filtrar por convocatoria según cargo y período
                        $q->whereHas('convocatoria', function ($convQ) use ($config) {
                            // Filtro por cargo - NO aplicar si es 'all'
                            if (!empty($config['cargo_id']) && $config['cargo_id'] !== 'all') {
                                $convQ->where('cargo_id', $config['cargo_id']);
                            }
                            
                            // Filtro por período electoral - NO aplicar si es 'all'
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
}