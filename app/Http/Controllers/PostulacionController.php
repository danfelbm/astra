<?php

namespace App\Http\Controllers;

use App\Models\Candidatura;
use App\Models\Convocatoria;
use App\Models\Postulacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PostulacionController extends Controller
{
    /**
     * Lista de postulaciones del usuario y convocatorias disponibles
     */
    public function index()
    {
        $usuario = Auth::user();
        
        // Obtener postulaciones del usuario
        $postulaciones = Postulacion::with(['convocatoria.cargo', 'convocatoria.periodoElectoral', 'convocatoria.territorio', 'convocatoria.departamento', 'convocatoria.municipio', 'convocatoria.localidad'])
            ->porUsuario($usuario->id)
            ->ordenadosPorFecha()
            ->get()
            ->map(function ($postulacion) {
                return [
                    'id' => $postulacion->id,
                    'convocatoria' => [
                        'id' => $postulacion->convocatoria->id,
                        'nombre' => $postulacion->convocatoria->nombre,
                        'cargo' => $postulacion->convocatoria->cargo->nombre,
                        'periodo' => $postulacion->convocatoria->periodoElectoral->nombre,
                        'estado_temporal' => $postulacion->convocatoria->getEstadoTemporal(),
                    ],
                    'estado' => $postulacion->estado,
                    'estado_label' => $postulacion->estado_label,
                    'estado_color' => $postulacion->estado_color,
                    'fecha_postulacion' => $postulacion->getFechaPostulacionFormateada(),
                    'tiene_candidatura_vinculada' => $postulacion->tieneCandidaturaVinculada(),
                    'comentarios_admin' => $postulacion->comentarios_admin,
                    'puede_editar' => $postulacion->puedeSerEditada(),
                    'created_at' => $postulacion->created_at->format('d/m/Y H:i'),
                ];
            });

        // Obtener convocatorias disponibles
        $convocatoriasDisponibles = Convocatoria::with(['cargo', 'periodoElectoral', 'territorio', 'departamento', 'municipio', 'localidad'])
            ->disponibles()
            ->get()
            ->filter(function ($convocatoria) use ($usuario) {
                return $convocatoria->esDisponibleParaUsuario($usuario->id);
            })
            ->map(function ($convocatoria) {
                return [
                    'id' => $convocatoria->id,
                    'nombre' => $convocatoria->nombre,
                    'descripcion' => $convocatoria->descripcion,
                    'cargo' => $convocatoria->cargo->nombre,
                    'periodo' => $convocatoria->periodoElectoral->nombre,
                    'estado_temporal' => $convocatoria->getEstadoTemporal(),
                    'estado_temporal_label' => $convocatoria->getEstadoTemporalLabel(),
                    'estado_temporal_color' => $convocatoria->getEstadoTemporalColor(),
                    'rango_fechas' => $convocatoria->getRangoFechas(),
                    'ubicacion' => $convocatoria->getUbicacionTexto(),
                    'numero_postulaciones' => $convocatoria->getNumeroPostulaciones(),
                ];
            })
            ->values();

        return Inertia::render('Postulaciones/Index', [
            'postulaciones' => $postulaciones,
            'convocatorias_disponibles' => $convocatoriasDisponibles,
        ]);
    }

    /**
     * Mostrar formulario de postulación para una convocatoria específica
     */
    public function show(Convocatoria $convocatoria)
    {
        $usuario = Auth::user();
        
        // Cargar relaciones geográficas
        $convocatoria->load(['cargo', 'periodoElectoral', 'territorio', 'departamento', 'municipio', 'localidad']);

        // Verificar si ya tiene una postulación (para ver/editar)
        $postulacionExistente = $convocatoria->getPostulacionUsuario($usuario->id);

        // Si no tiene postulación, verificar que pueda crear una nueva
        if (!$postulacionExistente && !$convocatoria->esDisponibleParaUsuario($usuario->id)) {
            return redirect()
                ->route('postulaciones.index')
                ->with('error', 'No puedes postularte a esta convocatoria.');
        }

        // Obtener candidatura aprobada del usuario (si existe)
        $candidaturaAprobada = Candidatura::obtenerAprobadaDelUsuario($usuario->id);

        return Inertia::render('Postulaciones/Form', [
            'convocatoria' => [
                'id' => $convocatoria->id,
                'nombre' => $convocatoria->nombre,
                'descripcion' => $convocatoria->descripcion,
                'cargo' => $convocatoria->cargo->nombre,
                'periodo' => $convocatoria->periodoElectoral->nombre,
                'formulario_postulacion' => $convocatoria->formulario_postulacion,
                'estado_temporal' => $convocatoria->getEstadoTemporal(),
                'estado_temporal_label' => $convocatoria->getEstadoTemporalLabel(),
                'rango_fechas' => $convocatoria->getRangoFechas(),
                'ubicacion' => $convocatoria->getUbicacionTexto(),
                'tiene_perfil_candidatura' => $convocatoria->tienePerfilCandidaturaEnFormulario(),
            ],
            'postulacion' => $postulacionExistente ? [
                'id' => $postulacionExistente->id,
                'formulario_data' => $postulacionExistente->formulario_data,
                'candidatura_snapshot' => $postulacionExistente->candidatura_snapshot,
                'estado' => $postulacionExistente->estado,
                'estado_label' => $postulacionExistente->estado_label,
                'comentarios_admin' => $postulacionExistente->comentarios_admin,
                'puede_editar' => $postulacionExistente->puedeSerEditada(),
            ] : null,
            'candidatura_aprobada' => $candidaturaAprobada ? [
                'id' => $candidaturaAprobada->id,
                'formulario_data' => $candidaturaAprobada->formulario_data,
                'version' => $candidaturaAprobada->version,
                'fecha_aprobacion' => $candidaturaAprobada->fecha_aprobacion,
            ] : null,
        ]);
    }

    /**
     * Crear nueva postulación o actualizar borrador existente
     */
    public function store(Request $request, Convocatoria $convocatoria)
    {
        $usuario = Auth::user();

        // Verificar si ya tiene una postulación existente
        $postulacionExistente = Postulacion::where('convocatoria_id', $convocatoria->id)
            ->where('user_id', $usuario->id)
            ->first();

        if ($postulacionExistente) {
            // Si ya existe, verificar que esté en estado editable
            if (!$postulacionExistente->puedeSerEditada()) {
                throw ValidationException::withMessages([
                    'convocatoria' => 'Tu postulación actual no puede ser editada. Estado: ' . $postulacionExistente->estado_label
                ]);
            }
        } else {
            // Si no existe postulación, verificar que la convocatoria esté disponible
            if (!$convocatoria->esDisponibleParaUsuario($usuario->id)) {
                throw ValidationException::withMessages([
                    'convocatoria' => 'No puedes postularte a esta convocatoria.'
                ]);
            }
        }

        // Validar formulario dinámico
        $this->validarFormularioDinamico($request, $convocatoria->formulario_postulacion);

        // Preparar datos de la postulación
        $datosPostulacion = [
            'convocatoria_id' => $convocatoria->id,
            'user_id' => $usuario->id,
            'formulario_data' => $request->formulario_data ?? [],
            'origen' => 'convocatoria', // Marca que fue creada desde convocatoria
        ];

        // Manejar candidatura vinculada si está seleccionada
        if ($request->has('candidatura_id') && $request->candidatura_id) {
            $candidatura = Candidatura::find($request->candidatura_id);
            
            if (!$candidatura || $candidatura->user_id !== $usuario->id || !$candidatura->esAprobada()) {
                throw ValidationException::withMessages([
                    'candidatura_id' => 'La candidatura seleccionada no es válida.'
                ]);
            }

            $datosPostulacion['candidatura_snapshot'] = $candidatura->generarSnapshotCompleto();
            $datosPostulacion['candidatura_id_origen'] = $candidatura->id;
        }

        // Crear o actualizar postulación
        $postulacion = Postulacion::updateOrCreate(
            [
                'convocatoria_id' => $convocatoria->id,
                'user_id' => $usuario->id,
            ],
            $datosPostulacion
        );

        // Si se está enviando (no solo guardando como borrador)
        if ($request->boolean('enviar')) {
            // Pasar el usuario como parámetro para el historial
            $resultado = $postulacion->enviar($usuario);
            if (!$resultado) {
                throw ValidationException::withMessages([
                    'postulacion' => 'No se pudo enviar la postulación. Verifica que esté en estado borrador.'
                ]);
            }
            $mensaje = 'Postulación enviada correctamente. Está pendiente de revisión administrativa.';
        } else {
            $mensaje = 'Postulación guardada como borrador.';
        }

        return redirect()
            ->route('postulaciones.index')
            ->with('success', $mensaje);
    }

    /**
     * Validar formulario dinámico según configuración
     */
    private function validarFormularioDinamico(Request $request, array $campos)
    {
        // Verificar si hay campos normales (no especiales) en el formulario
        $camposNormales = array_filter($campos, function($campo) {
            return $campo['type'] !== 'perfil_candidatura';
        });
        
        // Solo requerir formulario_data si hay campos normales
        $rules = [];
        $messages = [];
        
        if (count($camposNormales) > 0) {
            $rules['formulario_data'] = 'required|array';
            $messages['formulario_data.required'] = 'Los datos del formulario son obligatorios.';
            $messages['formulario_data.array'] = 'Los datos del formulario deben ser un arreglo válido.';
        } else {
            // Si solo hay campos especiales, formulario_data puede ser opcional
            $rules['formulario_data'] = 'nullable|array';
        }

        foreach ($campos as $campo) {
            $fieldName = "formulario_data.{$campo['id']}";
            $fieldRules = [];

            // Validar campo especial perfil_candidatura
            if ($campo['type'] === 'perfil_candidatura') {
                if ($campo['required'] ?? false) {
                    $rules['candidatura_id'] = 'required|exists:candidaturas,id';
                    $messages['candidatura_id.required'] = 'Debes seleccionar tu perfil de candidatura.';
                    $messages['candidatura_id.exists'] = 'El perfil de candidatura seleccionado no es válido.';
                }
                continue;
            }

            // Campo requerido
            if ($campo['required'] ?? false) {
                $fieldRules[] = 'required';
            }

            // Validaciones por tipo
            switch ($campo['type']) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'text':
                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:1000';
                    break;
                case 'select':
                case 'radio':
                    if (!empty($campo['options'])) {
                        $fieldRules[] = 'in:' . implode(',', $campo['options']);
                    }
                    break;
                case 'checkbox':
                    $fieldRules[] = 'array';
                    break;
                case 'file':
                    // Los archivos se suben por separado y se envían como array de rutas
                    $fieldRules[] = 'array';
                    break;
            }

            if (!empty($fieldRules)) {
                $rules[$fieldName] = implode('|', $fieldRules);
                $messages["{$fieldName}.required"] = "El campo '{$campo['title']}' es obligatorio.";
            }
        }

        $request->validate($rules, $messages);
    }

    /**
     * API: Obtener convocatorias disponibles para el usuario
     */
    public function convocatoriasDisponibles()
    {
        $usuario = Auth::user();

        $convocatorias = Convocatoria::with(['cargo', 'periodoElectoral', 'territorio', 'departamento', 'municipio', 'localidad'])
            ->disponibles()
            ->get()
            ->filter(function ($convocatoria) use ($usuario) {
                return $convocatoria->esDisponibleParaUsuario($usuario->id);
            })
            ->map(function ($convocatoria) {
                return [
                    'id' => $convocatoria->id,
                    'nombre' => $convocatoria->nombre,
                    'cargo' => $convocatoria->cargo->nombre,
                    'periodo' => $convocatoria->periodoElectoral->nombre,
                    'estado_temporal' => $convocatoria->getEstadoTemporal(),
                    'rango_fechas' => $convocatoria->getRangoFechas(),
                ];
            })
            ->values();

        return response()->json($convocatorias);
    }

    /**
     * API: Obtener candidaturas aprobadas del usuario para selector
     */
    public function misCandidaturasAprobadas()
    {
        $usuario = Auth::user();
        
        $candidatura = Candidatura::obtenerAprobadaDelUsuario($usuario->id);

        if (!$candidatura) {
            return response()->json([]);
        }

        return response()->json([
            [
                'id' => $candidatura->id,
                'version' => $candidatura->version,
                'fecha_aprobacion' => $candidatura->fecha_aprobacion,
                'resumen' => "Perfil v{$candidatura->version} - Aprobado {$candidatura->fecha_aprobacion}",
            ]
        ]);
    }
}
