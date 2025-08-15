<?php

namespace App\Http\Controllers;

use App\Models\Formulario;
use App\Models\FormularioRespuesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class FormularioPublicController extends Controller
{
    /**
     * Mostrar lista de formularios disponibles para el usuario
     */
    public function index(Request $request): Response
    {
        $query = Formulario::query()
            ->with(['categoria'])
            ->where('estado', 'publicado')
            ->where('activo', true);
        
        // Filtrar por categoría si se especifica
        if ($request->filled('categoria')) {
            $query->where('categoria_id', $request->categoria);
        }
        
        // Filtrar por búsqueda
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('titulo', 'like', "%{$request->search}%")
                  ->orWhere('descripcion', 'like', "%{$request->search}%");
            });
        }
        
        // Solo mostrar formularios disponibles por fecha
        $query->where(function ($q) {
            $q->whereNull('fecha_inicio')
              ->orWhere('fecha_inicio', '<=', now());
        })->where(function ($q) {
            $q->whereNull('fecha_fin')
              ->orWhere('fecha_fin', '>=', now());
        });
        
        // Si el usuario no está autenticado, solo mostrar formularios que permiten visitantes
        if (!Auth::check()) {
            $query->where('permite_visitantes', true);
        }
        
        $formularios = $query->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();
        
        // Obtener categorías para filtros
        $categorias = \App\Models\FormularioCategoria::whereHas('formularios', function ($q) {
            $q->where('estado', 'publicado')
              ->where('activo', true);
        })->get();
        
        // Agregar información de respuestas del usuario si está autenticado
        if (Auth::check()) {
            $usuario = Auth::user();
            $respuestas = FormularioRespuesta::where('usuario_id', $usuario->id)
                ->whereIn('formulario_id', $formularios->pluck('id'))
                ->get()
                ->keyBy('formulario_id');
            
            $formularios->getCollection()->transform(function ($formulario) use ($respuestas) {
                $formulario->usuario_ha_respondido = isset($respuestas[$formulario->id]) && $respuestas[$formulario->id]->estado === 'enviado';
                $formulario->tiene_borrador = isset($respuestas[$formulario->id]) && $respuestas[$formulario->id]->estado === 'borrador';
                return $formulario;
            });
        }
        
        return Inertia::render('Formularios/Index', [
            'formularios' => $formularios,
            'categorias' => $categorias,
            'filters' => $request->only(['search', 'categoria']),
        ]);
    }
    
    /**
     * Mostrar el formulario para llenar
     */
    public function show($slug): Response
    {
        $formulario = Formulario::where('slug', $slug)
            ->with(['categoria'])
            ->firstOrFail();
        
        // Verificar si el formulario está disponible
        if (!$formulario->estaDisponible()) {
            return Inertia::render('Formularios/NoDisponible', [
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
            
            return Inertia::render('Formularios/SinAcceso', [
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
                return Inertia::render('Formularios/YaRespondido', [
                    'mensaje' => 'Ya has respondido este formulario.',
                ]);
            }
        }
        
        return Inertia::render('Formularios/Show', [
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
        
        return Inertia::render('Formularios/Success', [
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
        $emailRespondiente = $respuesta->getEmailRespondiente();
        if ($emailRespondiente) {
            // TODO: Implementar email de confirmación
            // Mail::to($emailRespondiente)->queue(new ConfirmacionFormulario($formulario, $respuesta));
        }
    }
}