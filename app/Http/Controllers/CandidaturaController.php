<?php

namespace App\Http\Controllers;

use App\Models\Candidatura;
use App\Models\CandidaturaConfig;
use App\Models\Convocatoria;
use App\Models\Postulacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CandidaturaController extends Controller
{
    /**
     * Dashboard de candidatura del usuario
     */
    public function index()
    {
        $usuario = Auth::user();
        $candidatura = Candidatura::delUsuario($usuario->id)->first();

        // Obtener configuración activa
        $config = CandidaturaConfig::obtenerConfiguracionActiva();

        if (!$config || !$config->tieneCampos()) {
            return Inertia::render('Candidaturas/NoDisponible', [
                'mensaje' => 'El sistema de candidaturas no está disponible en este momento. Los administradores están configurando los campos requeridos.',
            ]);
        }

        // Verificar si hay campos editables para candidaturas aprobadas
        $hayEditables = $config->obtenerCampos() ? collect($config->obtenerCampos())->some('editable', true) : false;
        $puedeEditar = $candidatura ? 
                      ($candidatura->esBorrador() || $candidatura->esRechazada() || 
                      ($candidatura->esAprobada() && $hayEditables)) : false;

        // Obtener resumen de aprobaciones si existe candidatura
        $resumenAprobaciones = null;
        $camposRechazados = [];
        if ($candidatura) {
            $candidatura->load('campoAprobaciones.aprobadoPor');
            $resumenAprobaciones = $candidatura->getEstadoAprobacionCampos();
            $camposRechazados = $candidatura->getCamposRechazados()->map(function ($aprobacion) {
                return [
                    'campo_id' => $aprobacion->campo_id,
                    'comentario' => $aprobacion->comentario,
                ];
            })->toArray();
        }

        return Inertia::render('Candidaturas/Dashboard', [
            'candidatura' => $candidatura ? [
                'id' => $candidatura->id,
                'estado' => $candidatura->estado,
                'estado_label' => $candidatura->estado_label,
                'estado_color' => $candidatura->estado_color,
                'version' => $candidatura->version,
                'comentarios_admin' => $candidatura->comentarios_admin,
                'fecha_aprobacion' => $candidatura->fecha_aprobacion,
                'created_at' => $candidatura->created_at->format('d/m/Y H:i'),
                'updated_at' => $candidatura->updated_at->format('d/m/Y H:i'),
                'tiene_datos' => !empty($candidatura->formulario_data),
                'puede_editar' => $puedeEditar,
                'hay_campos_editables' => $hayEditables,
                'resumen_aprobaciones' => $resumenAprobaciones,
                'campos_rechazados' => $camposRechazados,
            ] : null,
            'configuracion' => [
                'disponible' => true,
                'resumen' => $config->resumen,
                'version' => $config->version,
            ],
        ]);
    }

    /**
     * Mostrar formulario para crear candidatura
     */
    public function create()
    {
        $usuario = Auth::user();
        
        // Verificar si ya tiene una candidatura
        $candidaturaExistente = Candidatura::delUsuario($usuario->id)->first();
        if ($candidaturaExistente) {
            return redirect()->route('candidaturas.edit', $candidaturaExistente);
        }

        // Obtener configuración de campos
        $config = CandidaturaConfig::obtenerConfiguracionActiva();
        
        if (!$config || !$config->tieneCampos()) {
            return redirect()->route('candidaturas.index')
                ->with('error', 'No hay configuración de candidaturas disponible.');
        }

        return Inertia::render('Candidaturas/Form', [
            'candidatura' => null,
            'configuracion_campos' => $config->obtenerCampos(),
            'is_editing' => false,
        ]);
    }

    /**
     * Guardar nueva candidatura
     */
    public function store(Request $request)
    {
        $usuario = Auth::user();
        
        // Verificar que no tenga candidatura existente
        if (Candidatura::delUsuario($usuario->id)->exists()) {
            throw ValidationException::withMessages([
                'general' => 'Ya tienes una candidatura creada. Solo puedes tener un perfil de candidatura.'
            ]);
        }

        // Obtener configuración y validar
        $config = CandidaturaConfig::obtenerConfiguracionActiva();
        if (!$config) {
            throw ValidationException::withMessages([
                'general' => 'No hay configuración de candidaturas disponible.'
            ]);
        }

        // Validar datos del formulario dinámico
        $this->validarFormularioDinamico($request, $config->obtenerCampos());

        // Buscar si hay un campo de tipo convocatoria en el formulario
        $convocatoriaId = null;
        $campoConvocatoria = null;
        
        foreach ($config->obtenerCampos() as $campo) {
            if ($campo['type'] === 'convocatoria') {
                $campoConvocatoria = $campo;
                // Buscar el valor de la convocatoria en los datos del formulario
                if (isset($request->formulario_data[$campo['id']])) {
                    $convocatoriaId = $request->formulario_data[$campo['id']];
                }
                break;
            }
        }

        // Si hay convocatoria seleccionada, validar que esté disponible
        $convocatoria = null;
        if ($convocatoriaId) {
            $convocatoria = Convocatoria::find($convocatoriaId);
            
            if (!$convocatoria) {
                throw ValidationException::withMessages([
                    'convocatoria' => 'La convocatoria seleccionada no existe.'
                ]);
            }

            // Verificar que la convocatoria esté disponible para el usuario
            if (!$convocatoria->esDisponibleParaUsuario($usuario->id)) {
                throw ValidationException::withMessages([
                    'convocatoria' => 'No puedes postularte a esta convocatoria. Puede que ya tengas una postulación o no cumplas con los requisitos geográficos.'
                ]);
            }
        }

        // Usar transacción para asegurar consistencia
        DB::beginTransaction();
        
        try {
            // Crear candidatura en estado PENDIENTE
            $candidatura = Candidatura::create([
                'user_id' => $usuario->id,
                'formulario_data' => $request->formulario_data,
                'estado' => Candidatura::ESTADO_PENDIENTE,
            ]);

            // Si hay convocatoria seleccionada, crear postulación automática SOLO si no existe
            $postulacionCreada = false;
            if ($convocatoria) {
                // Verificar si el usuario ya tiene una postulación para esta convocatoria
                if (!Postulacion::usuarioTienePostulacion($usuario->id, $convocatoria->id)) {
                    // Crear postulación en estado "enviada"
                    $postulacion = Postulacion::create([
                        'convocatoria_id' => $convocatoria->id,
                        'user_id' => $usuario->id,
                        'formulario_data' => [], // Vacío porque los datos están en la candidatura
                        'candidatura_snapshot' => $candidatura->generarSnapshotCompleto(),
                        'candidatura_id_origen' => $candidatura->id,
                        'origen' => 'candidatura', // Marca que fue creada desde candidatura
                        'estado' => Postulacion::ESTADO_ENVIADA,
                        'fecha_postulacion' => now(),
                    ]);

                    // Registrar en el historial
                    \App\Models\PostulacionHistorial::crearRegistro(
                        $postulacion,
                        Postulacion::ESTADO_BORRADOR,
                        Postulacion::ESTADO_ENVIADA,
                        $usuario,
                        null,
                        'Postulación creada automáticamente desde formulario de candidatura'
                    );
                    
                    $postulacionCreada = true;
                }
            }

            DB::commit();

            // Mensaje de éxito diferenciado
            $mensaje = 'Perfil de candidatura enviado correctamente. Está pendiente de revisión administrativa.';
            if ($convocatoria) {
                if ($postulacionCreada) {
                    $mensaje .= ' Además, se ha creado automáticamente tu postulación a la convocatoria "' . $convocatoria->nombre . '".';
                } else {
                    $mensaje .= ' Ya tienes una postulación registrada para la convocatoria "' . $convocatoria->nombre . '".';
                }
            }

            return redirect()->route('candidaturas.index')
                ->with('success', $mensaje);
                
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Ver candidatura específica
     */
    public function show(Candidatura $candidatura)
    {
        // Solo el propietario puede ver su candidatura
        if ($candidatura->user_id !== Auth::id()) {
            abort(403);
        }

        // Cargar aprobaciones de campos
        $candidatura->load('campoAprobaciones.aprobadoPor');

        // Obtener configuración para mostrar estructura
        $config = CandidaturaConfig::obtenerConfiguracionActiva();

        // Obtener aprobaciones y resumen
        $campoAprobaciones = $candidatura->getCamposAprobaciones();
        $resumenAprobaciones = $candidatura->getEstadoAprobacionCampos();

        return Inertia::render('Candidaturas/Show', [
            'candidatura' => [
                'id' => $candidatura->id,
                'formulario_data' => $candidatura->formulario_data,
                'estado' => $candidatura->estado,
                'estado_label' => $candidatura->estado_label,
                'estado_color' => $candidatura->estado_color,
                'version' => $candidatura->version,
                'comentarios_admin' => $candidatura->comentarios_admin,
                'fecha_aprobacion' => $candidatura->fecha_aprobacion,
                'created_at' => $candidatura->created_at->format('d/m/Y H:i'),
                'updated_at' => $candidatura->updated_at->format('d/m/Y H:i'),
            ],
            'configuracion_campos' => $config ? $config->obtenerCampos() : [],
            'campo_aprobaciones' => $campoAprobaciones->map(function ($aprobacion) {
                return [
                    'campo_id' => $aprobacion->campo_id,
                    'aprobado' => $aprobacion->aprobado,
                    'estado_label' => $aprobacion->estado_label,
                    'estado_color' => $aprobacion->estado_color,
                    'comentario' => $aprobacion->comentario,
                    'aprobado_por' => $aprobacion->aprobadoPor ? [
                        'name' => $aprobacion->aprobadoPor->name,
                    ] : null,
                    'fecha_aprobacion' => $aprobacion->fecha_aprobacion,
                ];
            }),
            'resumen_aprobaciones' => $resumenAprobaciones,
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Candidatura $candidatura)
    {
        // Solo el propietario puede editar su candidatura
        if ($candidatura->user_id !== Auth::id()) {
            abort(403);
        }

        // Obtener configuración de campos
        $config = CandidaturaConfig::obtenerConfiguracionActiva();
        
        if (!$config || !$config->tieneCampos()) {
            return redirect()->route('candidaturas.index')
                ->with('error', 'No hay configuración de candidaturas disponible.');
        }

        // Verificar permisos de edición - PENDIENTE no es editable
        if ($candidatura->estaPendiente()) {
            return redirect()->route('candidaturas.index')
                ->with('error', 'No puedes editar una candidatura que está pendiente de revisión administrativa.');
        }
        
        $hayEditables = collect($config->obtenerCampos())->some('editable', true);
        $puedeEditar = $candidatura->esBorrador() || $candidatura->esRechazada() || 
                      ($candidatura->esAprobada() && $hayEditables);
        
        if (!$puedeEditar) {
            return redirect()->route('candidaturas.index')
                ->with('error', 'No puedes editar esta candidatura en su estado actual.');
        }

        // Log para depuración de datos del formulario
        \Log::info('Datos de candidatura para edición:', [
            'candidatura_id' => $candidatura->id,
            'formulario_data' => $candidatura->formulario_data,
        ]);

        return Inertia::render('Candidaturas/Form', [
            'candidatura' => [
                'id' => $candidatura->id,
                'formulario_data' => $candidatura->formulario_data,
                'estado' => $candidatura->estado,
                'estado_label' => $candidatura->estado_label,
                'version' => $candidatura->version,
                'comentarios_admin' => $candidatura->comentarios_admin,
            ],
            'configuracion_campos' => $config->obtenerCampos(),
            'is_editing' => true,
        ]);
    }

    /**
     * Actualizar candidatura existente
     */
    public function update(Request $request, Candidatura $candidatura)
    {
        // Solo el propietario puede actualizar su candidatura
        if ($candidatura->user_id !== Auth::id()) {
            abort(403);
        }

        // Verificar que no esté en estado PENDIENTE
        if ($candidatura->estaPendiente()) {
            throw ValidationException::withMessages([
                'general' => 'No puedes editar una candidatura que está pendiente de revisión administrativa.'
            ]);
        }

        // Obtener configuración y validar
        $config = CandidaturaConfig::obtenerConfiguracionActiva();
        if (!$config) {
            throw ValidationException::withMessages([
                'general' => 'No hay configuración de candidaturas disponible.'
            ]);
        }

        $configuracionCampos = $config->obtenerCampos();

        // Validar permisos de edición según estado
        if ($candidatura->esAprobada()) {
            // Para candidaturas aprobadas, solo se pueden editar campos marcados como editables
            $datosActuales = $candidatura->formulario_data ?? [];
            $cambiosDetectados = [];
            
            foreach ($request->formulario_data as $campoId => $valorNuevo) {
                $valorActual = $datosActuales[$campoId] ?? null;
                $valorNuevoSerializado = is_array($valorNuevo) ? json_encode($valorNuevo) : $valorNuevo;
                $valorActualSerializado = is_array($valorActual) ? json_encode($valorActual) : $valorActual;
                
                if ($valorNuevoSerializado !== $valorActualSerializado) {
                    $cambiosDetectados[$campoId] = $valorNuevo;
                }
            }

            // Validar que solo se editen campos editables
            if (!$candidatura->puedeEditarCampos($cambiosDetectados, $configuracionCampos)) {
                throw ValidationException::withMessages([
                    'general' => 'Solo puedes editar campos marcados como editables en candidaturas aprobadas.'
                ]);
            }
        } elseif (!$candidatura->esBorrador() && !$candidatura->esRechazada()) {
            throw ValidationException::withMessages([
                'general' => 'No puedes editar esta candidatura en su estado actual.'
            ]);
        }

        // Validar datos del formulario dinámico
        $this->validarFormularioDinamico($request, $configuracionCampos);

        // Determinar si requiere re-aprobación (para candidaturas aprobadas que se editen)
        $datosAnteriores = $candidatura->formulario_data ?? [];
        $datosNuevos = $request->formulario_data;
        
        // Comparar datos de forma segura (manejando arrays anidados)
        $cambios = $this->compararDatosFormulario($datosNuevos, $datosAnteriores);
        
        // Determinar nuevo estado según flujo PENDIENTE
        $nuevoEstado = $candidatura->estado;
        $limpiarCamposAdmin = false;
        
        if ($candidatura->esRechazada()) {
            // Si era rechazada, reenviar a PENDIENTE para nueva revisión
            $nuevoEstado = Candidatura::ESTADO_PENDIENTE;
            $limpiarCamposAdmin = true;
        } elseif ($candidatura->esBorrador()) {
            // Si era borrador, enviar a PENDIENTE para revisión
            $nuevoEstado = Candidatura::ESTADO_PENDIENTE;
        } elseif ($candidatura->esAprobada() && !empty($cambios)) {
            // Si era aprobada y hay cambios, enviar a PENDIENTE para nueva revisión
            $nuevoEstado = Candidatura::ESTADO_PENDIENTE;
            $limpiarCamposAdmin = true;
        }
        
        // Actualizar candidatura
        $updateData = [
            'formulario_data' => $datosNuevos,
            'estado' => $nuevoEstado,
        ];

        if ($limpiarCamposAdmin) {
            $updateData['aprobado_por'] = null;
            $updateData['aprobado_at'] = null;
            $updateData['comentarios_admin'] = null;
        }

        // Incrementar versión si hubo cambios significativos
        // Hacerlo ANTES del update para que el Observer capture la versión correcta
        if (!empty($cambios)) {
            $updateData['version'] = $candidatura->version + 1;
        }

        $candidatura->update($updateData);

        // Si el estado cambió a PENDIENTE, verificar si hay convocatoria para crear postulación
        $estadoFinal = $candidatura->fresh()->estado;
        $postulacionCreada = false;
        
        if ($estadoFinal === Candidatura::ESTADO_PENDIENTE && $nuevoEstado === Candidatura::ESTADO_PENDIENTE) {
            // Buscar si hay convocatoria en los datos del formulario
            $convocatoriaId = null;
            foreach ($configuracionCampos as $campo) {
                if ($campo['type'] === 'convocatoria' && isset($datosNuevos[$campo['id']])) {
                    $convocatoriaId = $datosNuevos[$campo['id']];
                    break;
                }
            }
            
            if ($convocatoriaId) {
                // Verificar que la convocatoria existe y está disponible
                $convocatoria = \App\Models\Convocatoria::find($convocatoriaId);
                if ($convocatoria && $convocatoria->esDisponibleParaUsuario($candidatura->user_id)) {
                    // Verificar si NO existe ya una postulación
                    if (!Postulacion::usuarioTienePostulacion($candidatura->user_id, $convocatoriaId)) {
                        // Crear postulación automática
                        $postulacion = Postulacion::create([
                            'convocatoria_id' => $convocatoriaId,
                            'user_id' => $candidatura->user_id,
                            'formulario_data' => [], // Vacío porque los datos están en la candidatura
                            'candidatura_snapshot' => $candidatura->generarSnapshotCompleto(),
                            'candidatura_id_origen' => $candidatura->id,
                            'origen' => 'candidatura',
                            'estado' => Postulacion::ESTADO_ENVIADA,
                            'fecha_postulacion' => now(),
                        ]);

                        // Registrar en el historial
                        \App\Models\PostulacionHistorial::crearRegistro(
                            $postulacion,
                            Postulacion::ESTADO_BORRADOR,
                            Postulacion::ESTADO_ENVIADA,
                            $candidatura->user,
                            null,
                            'Postulación creada automáticamente desde actualización de candidatura'
                        );
                        
                        $postulacionCreada = true;
                    }
                }
            }
        }

        // Mensaje según el cambio de estado
        if ($estadoFinal === Candidatura::ESTADO_PENDIENTE) {
            $mensaje = 'Candidatura enviada para revisión administrativa.';
            if ($postulacionCreada) {
                $mensaje .= ' Además, se ha creado automáticamente tu postulación a la convocatoria seleccionada.';
            }
        } elseif ($estadoFinal === Candidatura::ESTADO_BORRADOR && !empty($cambios)) {
            $mensaje = 'Candidatura actualizada. Requiere nueva aprobación administrativa.';
        } else {
            $mensaje = 'Candidatura actualizada correctamente.';
        }

        return redirect()->route('candidaturas.index')->with('success', $mensaje);
    }

    /**
     * Obtener historial de cambios de una candidatura
     */
    public function historial(Candidatura $candidatura)
    {
        // Solo el propietario puede ver el historial de su candidatura
        if ($candidatura->user_id !== Auth::id()) {
            abort(403);
        }

        $historial = $candidatura->historial()
            ->with('createdBy:id,name')
            ->ordenadoPorVersion('desc')
            ->paginate(10);

        // Formatear datos para el frontend
        $historialFormateado = $historial->through(function ($entrada) {
            return [
                'id' => $entrada->id,
                'version' => $entrada->version,
                'formulario_data' => $entrada->formulario_data,
                'formulario_data_con_nombres' => $entrada->formulario_data_con_nombres,
                'configuracion_campos_en_momento' => $entrada->configuracion_campos_en_momento,
                'estado_en_momento' => $entrada->estado_en_momento,
                'estado_label' => $entrada->estado_label,
                'estado_color' => $entrada->estado_color,
                'comentarios_admin_en_momento' => $entrada->comentarios_admin_en_momento,
                'motivo_cambio' => $entrada->motivo_cambio,
                'resumen_cambios' => $entrada->resumen_cambios,
                'fecha_formateada' => $entrada->fecha_formateada,
                'created_by' => $entrada->createdBy ? $entrada->createdBy->name : 'Sistema',
                'created_at' => $entrada->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json($historialFormateado);
    }

    /**
     * Validar formulario dinámico según configuración
     */
    private function validarFormularioDinamico(Request $request, array $campos)
    {
        // Usar el servicio de campos condicionales
        $conditionalService = new \App\Services\ConditionalFieldService();
        
        // Obtener solo los campos visibles según las condiciones
        $formData = $request->formulario_data ?? [];
        $camposVisibles = $conditionalService->getVisibleFields($campos, $formData);
        
        // Validar que no haya referencias circulares en las condiciones
        $erroresCondiciones = $conditionalService->validateConditionalRules($campos);
        if (!empty($erroresCondiciones)) {
            throw ValidationException::withMessages([
                'general' => 'Error en la configuración de campos condicionales: ' . implode(', ', $erroresCondiciones)
            ]);
        }
        
        $rules = ['formulario_data' => 'required|array'];
        $messages = [];

        foreach ($camposVisibles as $campo) {
            $fieldName = "formulario_data.{$campo['id']}";
            $fieldRules = [];

            // Campo requerido (solo si es visible)
            if ($campo['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                // Si NO es requerido, permitir valores null
                $fieldRules[] = 'nullable';
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
                    // Aumentar límite para campos de texto largo
                    if ($campo['type'] === 'textarea') {
                        $fieldRules[] = 'max:10000';
                    } else {
                        $fieldRules[] = 'max:1000';
                    }
                    break;
                case 'select':
                case 'radio':
                    // Para select y radio, validar que el valor esté en las opciones
                    if (!empty($campo['options'])) {
                        // Usar callback para validación personalizada cuando las opciones tienen comas
                        $fieldRules[] = function ($attribute, $value, $fail) use ($campo) {
                            if (!in_array($value, $campo['options'])) {
                                $fail("El valor seleccionado para '{$campo['title']}' no es válido.");
                            }
                        };
                    }
                    break;
                case 'checkbox':
                    $fieldRules[] = 'array';
                    if (!empty($campo['options'])) {
                        // Validar que cada elemento del array esté en las opciones
                        $fieldRules[] = function ($attribute, $value, $fail) use ($campo) {
                            if (is_array($value)) {
                                foreach ($value as $item) {
                                    if (!in_array($item, $campo['options'])) {
                                        $fail("Una de las opciones seleccionadas para '{$campo['title']}' no es válida.");
                                        break;
                                    }
                                }
                            }
                        };
                    }
                    break;
                case 'file':
                    // Los archivos se suben por separado y se envían como array de rutas
                    $fieldRules[] = 'array';
                    break;
                case 'repeater':
                    // Los campos repeater son arrays de objetos
                    $fieldRules[] = 'array';
                    // Validar min/max items si están configurados
                    if (isset($campo['repeaterConfig']['minItems']) && $campo['repeaterConfig']['minItems'] > 0) {
                        $fieldRules[] = 'min:' . $campo['repeaterConfig']['minItems'];
                    }
                    if (isset($campo['repeaterConfig']['maxItems'])) {
                        $fieldRules[] = 'max:' . $campo['repeaterConfig']['maxItems'];
                    }
                    break;
                case 'datepicker':
                    // Campo de fecha mejorado
                    $fieldRules[] = 'date';
                    break;
                case 'disclaimer':
                    // Campo de disclaimer debe ser un objeto con accepted y timestamp
                    if ($campo['required'] ?? false) {
                        // Para disclaimers requeridos, validar que esté aceptado
                        $fieldRules[] = function ($attribute, $value, $fail) use ($campo) {
                            if (!is_array($value) || !isset($value['accepted']) || !$value['accepted']) {
                                $fail("Debes aceptar '{$campo['title']}' para continuar.");
                            }
                        };
                    }
                    break;
                case 'convocatoria':
                    // Campo de selección de convocatoria
                    if ($campo['required'] ?? false) {
                        $fieldRules[] = 'exists:convocatorias,id';
                    }
                    break;
            }

            if (!empty($fieldRules)) {
                $rules[$fieldName] = $fieldRules;
                $messages["{$fieldName}.required"] = "El campo '{$campo['title']}' es obligatorio.";
            }
        }

        $request->validate($rules, $messages);
    }

    /**
     * Comparar datos de formulario manejando arrays anidados de forma segura
     */
    private function compararDatosFormulario(array $datosNuevos, array $datosAnteriores): array
    {
        $cambios = [];
        
        // Comparar cada campo
        foreach ($datosNuevos as $campo => $valorNuevo) {
            $valorAnterior = $datosAnteriores[$campo] ?? null;
            
            // Serializar valores para comparación segura
            $valorNuevoSerializado = is_array($valorNuevo) ? json_encode($valorNuevo) : $valorNuevo;
            $valorAnteriorSerializado = is_array($valorAnterior) ? json_encode($valorAnterior) : $valorAnterior;
            
            // Si son diferentes, registrar el cambio
            if ($valorNuevoSerializado !== $valorAnteriorSerializado) {
                $cambios[$campo] = $valorNuevo;
            }
        }
        
        return $cambios;
    }

    /**
     * API: Obtener estado de candidatura del usuario
     */
    public function getEstadoCandidatura()
    {
        $usuario = Auth::user();
        $candidatura = Candidatura::delUsuario($usuario->id)->first();

        if (!$candidatura) {
            return response()->json(['existe' => false]);
        }

        return response()->json([
            'existe' => true,
            'estado' => $candidatura->estado,
            'estado_label' => $candidatura->estado_label,
            'puede_editar' => $candidatura->esBorrador() || $candidatura->esRechazada(),
            'tiene_comentarios' => !empty($candidatura->comentarios_admin),
        ]);
    }

    /**
     * Autoguardado de candidatura nueva
     */
    public function autosave(Request $request)
    {
        try {
            $usuario = Auth::user();
            
            // Verificar si ya existe una candidatura en borrador
            $candidatura = Candidatura::delUsuario($usuario->id)
                ->whereIn('estado', ['borrador', 'borrador_auto'])
                ->first();
            
            // Obtener la configuración activa
            $configuracion = CandidaturaConfig::where('activo', true)->first();
            
            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay configuración de candidatura activa',
                ], 400);
            }
            
            // Si no existe, crear una nueva
            if (!$candidatura) {
                $candidatura = new Candidatura();
                $candidatura->user_id = $usuario->id;
                $candidatura->estado = 'borrador';
                $candidatura->version = 1;
                $candidatura->formulario_data = [];
                
                // Asignar tenant_id manualmente porque saveQuietly() no dispara eventos del modelo
                // y por tanto el trait HasTenant no puede asignarlo automáticamente
                $candidatura->tenant_id = $usuario->tenant_id;
            }
            
            // Actualizar los datos del formulario (incluyendo convocatoria si existe)
            // IMPORTANTE: El autoguardado NO crea postulaciones, solo guarda el valor de convocatoria
            // La postulación se crea cuando el estado cambia de borrador a pendiente
            $datosFormulario = $request->input('formulario_data', []);
            
            // Limpiar datos vacíos para campos no requeridos
            $datosLimpios = $this->limpiarDatosAutoguardado($datosFormulario, $configuracion->campos);
            
            // Mezclar con datos existentes (mantener campos no enviados)
            $candidatura->formulario_data = array_merge(
                $candidatura->formulario_data ?? [],
                $datosLimpios
            );
            
            // Marcar como autoguardado
            $candidatura->ultimo_autoguardado = now();
            
            // Guardar sin validación completa y sin disparar eventos del Observer
            // Esto evita crear entradas redundantes en el historial
            $candidatura->saveQuietly();
            
            return response()->json([
                'success' => true,
                'message' => 'Borrador guardado automáticamente',
                'candidatura_id' => $candidatura->id,
                'timestamp' => $candidatura->ultimo_autoguardado->format('H:i:s'),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en autoguardado de candidatura: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el borrador',
            ], 500);
        }
    }
    
    /**
     * Autoguardado de candidatura existente
     */
    public function autosaveExisting(Request $request, Candidatura $candidatura)
    {
        try {
            // Verificar que el usuario es dueño de la candidatura
            if ($candidatura->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado',
                ], 403);
            }
            
            // Solo permitir autoguardado en estados editables
            if (!in_array($candidatura->estado, ['borrador', 'borrador_auto', 'rechazado'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'La candidatura no se puede editar en su estado actual',
                ], 400);
            }
            
            // Obtener la configuración activa
            $configuracion = CandidaturaConfig::where('activo', true)->first();
            
            if (!$configuracion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay configuración de candidatura activa',
                ], 400);
            }
            
            // Actualizar los datos del formulario (incluyendo convocatoria si existe)
            // IMPORTANTE: El autoguardado NO crea postulaciones, solo guarda el valor de convocatoria
            // La postulación se crea cuando el estado cambia de borrador a pendiente
            $datosFormulario = $request->input('formulario_data', []);
            
            // Limpiar datos vacíos para campos no requeridos
            $datosLimpios = $this->limpiarDatosAutoguardado($datosFormulario, $configuracion->campos);
            
            // Mezclar con datos existentes
            $candidatura->formulario_data = array_merge(
                $candidatura->formulario_data ?? [],
                $datosLimpios
            );
            
            // Marcar como autoguardado
            $candidatura->ultimo_autoguardado = now();
            
            // Guardar sin validación completa y sin disparar eventos del Observer
            // Esto evita crear entradas redundantes en el historial
            $candidatura->saveQuietly();
            
            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados automáticamente',
                'candidatura_id' => $candidatura->id,
                'timestamp' => $candidatura->ultimo_autoguardado->format('H:i:s'),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en autoguardado de candidatura existente: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los cambios',
            ], 500);
        }
    }
    
    /**
     * Limpiar datos para autoguardado (convertir strings vacíos a null para campos opcionales)
     */
    private function limpiarDatosAutoguardado(array $datos, array $configuracionCampos): array
    {
        $datosLimpios = [];
        
        foreach ($datos as $campoId => $valor) {
            // Buscar la configuración del campo
            $campo = collect($configuracionCampos)->firstWhere('id', $campoId);
            
            // Si el valor es string vacío y el campo no es requerido, convertir a null
            if ($valor === '' && $campo && !($campo['required'] ?? false)) {
                $datosLimpios[$campoId] = null;
            } else {
                $datosLimpios[$campoId] = $valor;
            }
        }
        
        return $datosLimpios;
    }
}
