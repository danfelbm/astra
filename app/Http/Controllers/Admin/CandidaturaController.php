<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidatura;
use App\Models\CandidaturaConfig;
use App\Models\CandidaturaCampoAprobacion;
use App\Models\User;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CandidaturaController extends Controller
{
    use HasAdvancedFilters;
    /**
     * Lista de candidaturas para revisión
     */
    public function index(Request $request)
    {
        $query = Candidatura::with(['user', 'aprobadoPor'])
            ->latest();

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'user_id', 'estado', 'version', 'aprobado_por', 'aprobado_at',
            'created_at', 'updated_at'
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['user.name', 'user.email', 'comentarios_admin'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Mantener compatibilidad con filtros simples existentes
        $this->applySimpleFilters($query, $request);

        $candidaturas = $query->paginate(20)->withQueryString();

        // Obtener configuración activa para calcular campos
        $configuracionActiva = CandidaturaConfig::obtenerConfiguracionActiva();
        $totalCampos = $configuracionActiva ? $configuracionActiva->contarCampos() : 0;

        // Enriquecer datos para el frontend
        $candidaturas->through(function ($candidatura) use ($totalCampos) {
            // Calcular campos llenados
            $camposLlenados = 0;
            if (!empty($candidatura->formulario_data) && is_array($candidatura->formulario_data)) {
                foreach ($candidatura->formulario_data as $value) {
                    if ($value !== null && $value !== '' && $value !== []) {
                        $camposLlenados++;
                    }
                }
            }

            return [
                'id' => $candidatura->id,
                'usuario' => [
                    'id' => $candidatura->user->id,
                    'name' => $candidatura->user->name,
                    'email' => $candidatura->user->email,
                ],
                'estado' => $candidatura->estado,
                'estado_label' => $candidatura->estado_label,
                'estado_color' => $candidatura->estado_color,
                'version' => $candidatura->version,
                'comentarios_admin' => $candidatura->comentarios_admin,
                'aprobado_por' => $candidatura->aprobadoPor ? [
                    'name' => $candidatura->aprobadoPor->name,
                    'email' => $candidatura->aprobadoPor->email,
                ] : null,
                'fecha_aprobacion' => $candidatura->fecha_aprobacion,
                'created_at' => $candidatura->created_at->toISOString(),
                'updated_at' => $candidatura->updated_at->toISOString(),
                'tiene_datos' => !empty($candidatura->formulario_data),
                'campos_llenados' => $camposLlenados,
                'total_campos' => $totalCampos,
                'porcentaje_completado' => $totalCampos > 0 ? round(($camposLlenados / $totalCampos) * 100) : 0,
                'esta_pendiente' => $candidatura->estaPendiente(),
            ];
        });

        return Inertia::render('Admin/Candidaturas/Index', [
            'candidaturas' => $candidaturas,
            'filters' => $request->only(['estado', 'search', 'advanced_filters']),
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
        ]);
    }
    
    /**
     * Aplicar filtros simples para mantener compatibilidad
     */
    protected function applySimpleFilters($query, $request)
    {
        // Solo aplicar si no hay filtros avanzados
        if (!$request->filled('advanced_filters')) {
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }
        }
    }
    
    /**
     * Obtener configuración de campos para filtros avanzados
     */
    public function getFilterFieldsConfig(): array
    {
        // Cargar usuarios revisores
        $revisores = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['admin', 'super_admin']);
        })->get()->map(fn($u) => [
            'value' => $u->id,
            'label' => $u->name
        ]);
        
        return [
            [
                'name' => 'user.name',
                'label' => 'Nombre del Usuario',
                'type' => 'text',
            ],
            [
                'name' => 'user.email',
                'label' => 'Email del Usuario',
                'type' => 'text',
            ],
            [
                'name' => 'estado',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'borrador', 'label' => 'Borrador'],
                    ['value' => 'aprobado', 'label' => 'Aprobado'],
                    ['value' => 'rechazado', 'label' => 'Rechazado'],
                ],
            ],
            [
                'name' => 'version',
                'label' => 'Versión',
                'type' => 'number',
            ],
            [
                'name' => 'aprobado_por',
                'label' => 'Aprobado Por',
                'type' => 'select',
                'options' => $revisores->toArray(),
            ],
            [
                'name' => 'aprobado_at',
                'label' => 'Fecha de Aprobación',
                'type' => 'datetime',
            ],
            [
                'name' => 'comentarios_admin',
                'label' => 'Comentarios del Admin',
                'type' => 'text',
            ],
            [
                'name' => 'created_at',
                'label' => 'Fecha de Creación',
                'type' => 'datetime',
            ],
        ];
    }

    /**
     * Ver detalles de una candidatura específica
     */
    public function show(Candidatura $candidatura)
    {
        $candidatura->load(['user', 'aprobadoPor', 'campoAprobaciones.aprobadoPor']);

        // Obtener configuración activa para mostrar estructura de campos
        $config = CandidaturaConfig::obtenerConfiguracionActiva();

        // Obtener aprobaciones de campos
        $campoAprobaciones = $candidatura->getCamposAprobaciones();
        
        // Calcular resumen con el total de campos de la configuración activa
        $totalCamposConfig = $config ? $config->contarCampos() : 0;
        $resumenBase = $candidatura->getEstadoAprobacionCampos();
        $resumenAprobaciones = [
            'total' => $totalCamposConfig,
            'aprobados' => $resumenBase['aprobados'] ?? 0,
            'rechazados' => $resumenBase['rechazados'] ?? 0,
            'pendientes' => $totalCamposConfig - ($resumenBase['aprobados'] ?? 0) - ($resumenBase['rechazados'] ?? 0),
            'porcentaje_aprobado' => $totalCamposConfig > 0 
                ? round((($resumenBase['aprobados'] ?? 0) / $totalCamposConfig) * 100, 2) 
                : 0,
        ];

        return Inertia::render('Admin/Candidaturas/Show', [
            'candidatura' => [
                'id' => $candidatura->id,
                'usuario' => [
                    'id' => $candidatura->user->id,
                    'name' => $candidatura->user->name,
                    'email' => $candidatura->user->email,
                ],
                'formulario_data' => $candidatura->formulario_data,
                'estado' => $candidatura->estado,
                'estado_label' => $candidatura->estado_label,
                'estado_color' => $candidatura->estado_color,
                'version' => $candidatura->version,
                'comentarios_admin' => $candidatura->comentarios_admin,
                'aprobado_por' => $candidatura->aprobadoPor ? [
                    'name' => $candidatura->aprobadoPor->name,
                    'email' => $candidatura->aprobadoPor->email,
                ] : null,
                'fecha_aprobacion' => $candidatura->fecha_aprobacion,
                'created_at' => $candidatura->created_at->toISOString(),
                'updated_at' => $candidatura->updated_at->toISOString(),
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
                        'id' => $aprobacion->aprobadoPor->id,
                        'name' => $aprobacion->aprobadoPor->name,
                        'email' => $aprobacion->aprobadoPor->email,
                    ] : null,
                    'fecha_aprobacion' => $aprobacion->fecha_aprobacion,
                ];
            }),
            'resumen_aprobaciones' => $resumenAprobaciones,
            'puede_aprobar_campos' => Auth::user()->hasPermission('candidaturas.aprobar_campos'),
        ]);
    }

    /**
     * Aprobar una candidatura
     */
    public function aprobar(Request $request, Candidatura $candidatura)
    {
        $request->validate([
            'comentarios' => 'nullable|string|max:1000',
        ]);

        if (!$candidatura->estaPendiente()) {
            throw ValidationException::withMessages([
                'estado' => 'Solo se pueden aprobar candidaturas pendientes de revisión.'
            ]);
        }

        $candidatura->aprobar(Auth::user(), $request->comentarios);

        return back()->with('success', 'Candidatura aprobada correctamente.');
    }

    /**
     * Rechazar una candidatura
     */
    public function rechazar(Request $request, Candidatura $candidatura)
    {
        $request->validate([
            'comentarios' => 'required|string|max:1000',
        ]);

        if (!$candidatura->estaPendiente()) {
            throw ValidationException::withMessages([
                'estado' => 'Solo se pueden rechazar candidaturas pendientes de revisión.'
            ]);
        }

        $candidatura->rechazar(Auth::user(), $request->comentarios);

        return back()->with('success', 'Candidatura rechazada. El usuario podrá editarla y reenviarla.');
    }

    /**
     * Vista de configuración de campos
     */
    public function configuracion()
    {
        $configuracionActiva = CandidaturaConfig::obtenerConfiguracionActiva();
        $historial = CandidaturaConfig::with('createdBy')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return Inertia::render('Admin/Candidaturas/Configuracion', [
            'configuracion_activa' => $configuracionActiva ? [
                'id' => $configuracionActiva->id,
                'campos' => $configuracionActiva->obtenerCampos(),
                'version' => $configuracionActiva->version,
                'resumen' => $configuracionActiva->resumen,
                'fecha_creacion' => $configuracionActiva->fecha_creacion,
                'created_by' => [
                    'name' => $configuracionActiva->createdBy->name,
                    'email' => $configuracionActiva->createdBy->email,
                ],
            ] : null,
            'historial' => $historial->map(function ($config) {
                return [
                    'id' => $config->id,
                    'version' => $config->version,
                    'estado' => $config->estado,
                    'color_estado' => $config->color_estado,
                    'resumen' => $config->resumen,
                    'fecha_creacion' => $config->fecha_creacion,
                    'created_by' => [
                        'name' => $config->createdBy->name,
                        'email' => $config->createdBy->email,
                    ],
                ];
            }),
        ]);
    }

    /**
     * Guardar nueva configuración de campos
     */
    public function guardarConfiguracion(Request $request)
    {
        $request->validate([
            'campos' => 'required|array|min:1',
            'campos.*.id' => 'required|string',
            'campos.*.type' => 'required|string|in:text,textarea,number,email,date,select,radio,checkbox,file,convocatoria,datepicker,disclaimer,repeater',
            'campos.*.title' => 'required|string|max:255',
            'campos.*.description' => 'nullable|string|max:500',
            'campos.*.required' => 'boolean',
            'campos.*.editable' => 'boolean',
            'campos.*.options' => 'array|nullable',
            'campos.*.convocatoriaConfig' => 'nullable|array',
            'campos.*.convocatoriaConfig.multiple' => 'nullable|boolean',
            'campos.*.convocatoriaConfig.filtrarPorUbicacion' => 'nullable|boolean',
            // Validación para datepicker
            'campos.*.datepickerConfig' => 'nullable|array',
            'campos.*.datepickerConfig.minDate' => 'nullable|date',
            'campos.*.datepickerConfig.maxDate' => 'nullable|date',
            'campos.*.datepickerConfig.format' => 'nullable|string',
            'campos.*.datepickerConfig.allowPastDates' => 'nullable|boolean',
            'campos.*.datepickerConfig.allowFutureDates' => 'nullable|boolean',
            // Validación para disclaimer
            'campos.*.disclaimerConfig' => 'nullable|array',
            'campos.*.disclaimerConfig.disclaimerText' => 'required_if:campos.*.type,disclaimer|string',
            'campos.*.disclaimerConfig.modalTitle' => 'nullable|string|max:255',
            'campos.*.disclaimerConfig.acceptButtonText' => 'nullable|string|max:50',
            'campos.*.disclaimerConfig.declineButtonText' => 'nullable|string|max:50',
            // Validación para repeater
            'campos.*.repeaterConfig' => 'nullable|array',
            'campos.*.repeaterConfig.minItems' => 'nullable|integer|min:0|max:50',
            'campos.*.repeaterConfig.maxItems' => 'nullable|integer|min:1|max:50',
            'campos.*.repeaterConfig.addButtonText' => 'nullable|string|max:100',
            'campos.*.repeaterConfig.removeButtonText' => 'nullable|string|max:100',
            'campos.*.repeaterConfig.fields' => 'nullable|array',
            'campos.*.repeaterConfig.fields.*.id' => 'required_with:campos.*.repeaterConfig.fields|string',
            'campos.*.repeaterConfig.fields.*.type' => 'required_with:campos.*.repeaterConfig.fields|string|in:text,textarea,number,email,date,select,datepicker',
            'campos.*.repeaterConfig.fields.*.title' => 'required_with:campos.*.repeaterConfig.fields|string|max:255',
            'campos.*.repeaterConfig.fields.*.required' => 'nullable|boolean',
            // Validación para campo numérico
            'campos.*.numberConfig' => 'nullable|array',
            'campos.*.numberConfig.min' => 'nullable|numeric',
            'campos.*.numberConfig.max' => 'nullable|numeric',
            'campos.*.numberConfig.step' => 'nullable|numeric|min:0',
            'campos.*.numberConfig.decimals' => 'nullable|integer|min:0|max:10',
        ]);

        $configuracion = CandidaturaConfig::crearConfiguracion(
            $request->campos,
            Auth::user()
        );

        return back()->with('success', 'Configuración de candidaturas actualizada correctamente.');
    }

    /**
     * API: Obtener candidaturas por estado
     */
    public function getCandidaturasPorEstado(string $estado)
    {
        $candidaturas = Candidatura::with('user')
            ->where('estado', $estado)
            ->latest()
            ->get();

        return response()->json($candidaturas);
    }

    /**
     * API: Obtener configuración activa de campos
     */
    public function getConfiguracionActiva()
    {
        $config = CandidaturaConfig::obtenerConfiguracionActiva();
        
        if (!$config) {
            return response()->json(['error' => 'No hay configuración activa'], 404);
        }

        return response()->json([
            'id' => $config->id,
            'campos' => $config->obtenerCampos(),
            'version' => $config->version,
        ]);
    }

    /**
     * API: Estadísticas de candidaturas
     */
    public function getEstadisticas()
    {
        $stats = [
            'total' => Candidatura::count(),
            'borradores' => Candidatura::borradores()->count(),
            'pendientes' => Candidatura::pendientes()->count(),
            'aprobadas' => Candidatura::aprobadas()->count(),
            'rechazadas' => Candidatura::rechazadas()->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Activar una configuración específica
     */
    public function activarConfiguracion(CandidaturaConfig $configuracion)
    {
        $configuracion->activar();

        return back()->with('success', 'Configuración activada correctamente.');
    }

    /**
     * Volver candidatura aprobada a estado borrador
     */
    public function volverABorrador(Request $request, Candidatura $candidatura)
    {
        // Validar que la candidatura esté aprobada o rechazada
        if (!$candidatura->esAprobada() && !$candidatura->esRechazada()) {
            return back()->withErrors(['error' => 'Solo se pueden volver a borrador candidaturas aprobadas o rechazadas.']);
        }

        // Validar motivo opcional
        $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        // Volver a borrador
        $candidatura->update([
            'estado' => Candidatura::ESTADO_BORRADOR,
            'aprobado_por' => null,
            'aprobado_at' => null,
            'comentarios_admin' => $request->motivo,
        ]);

        // TODO: Enviar notificación al usuario sobre el cambio de estado

        return back()->with('success', 'Candidatura devuelta a estado borrador correctamente.');
    }

    /**
     * Obtener historial de cambios de una candidatura
     */
    public function historial(Request $request, Candidatura $candidatura)
    {
        $perPage = $request->get('per_page', 10);
        
        $historial = $candidatura->historial()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Formatear datos para el frontend
        $historial->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'version' => $item->version,
                'estado' => $item->estado_en_momento,
                'estado_label' => $item->estado_label,
                'estado_color' => $item->estado_color,
                'estado_original' => $item->estado_original,
                'formulario_data' => $item->formulario_data,
                'formulario_data_con_nombres' => $item->formulario_data_con_nombres,
                'motivo_cambio' => $item->motivo_cambio,
                'created_by' => $item->createdBy?->name ?? 'Usuario eliminado',
                'comentarios_admin_en_momento' => $item->comentarios_admin_en_momento,
                'fecha_formateada' => $item->fecha_formateada,
                'resumen_cambios' => $item->resumen_cambios,
                'created_at' => $item->created_at,
            ];
        });

        return response()->json($historial);
    }

    /**
     * Aprobar un campo individual de la candidatura
     */
    public function aprobarCampo(Request $request, Candidatura $candidatura, string $campoId)
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('candidaturas.aprobar_campos')) {
            return response()->json(['error' => 'No tienes permisos para aprobar campos'], 403);
        }

        $request->validate([
            'comentario' => 'nullable|string|max:500',
        ]);

        // Obtener el valor actual del campo
        $valorActual = $candidatura->formulario_data[$campoId] ?? null;

        // Crear o actualizar la aprobación del campo
        $aprobacion = CandidaturaCampoAprobacion::crearOActualizar(
            $candidatura,
            $campoId,
            true, // aprobado
            Auth::user(),
            $request->comentario,
            $valorActual
        );

        return response()->json([
            'success' => true,
            'message' => 'Campo aprobado correctamente',
            'aprobacion' => [
                'campo_id' => $aprobacion->campo_id,
                'aprobado' => $aprobacion->aprobado,
                'estado_label' => $aprobacion->estado_label,
                'comentario' => $aprobacion->comentario,
                'aprobado_por' => [
                    'id' => Auth::user()->id,
                    'name' => Auth::user()->name,
                ],
                'fecha_aprobacion' => $aprobacion->fecha_aprobacion,
            ],
        ]);
    }

    /**
     * Rechazar un campo individual de la candidatura
     */
    public function rechazarCampo(Request $request, Candidatura $candidatura, string $campoId)
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('candidaturas.aprobar_campos')) {
            return response()->json(['error' => 'No tienes permisos para rechazar campos'], 403);
        }

        $request->validate([
            'comentario' => 'required|string|max:500',
        ]);

        // Obtener el valor actual del campo
        $valorActual = $candidatura->formulario_data[$campoId] ?? null;

        // Crear o actualizar la aprobación del campo
        $aprobacion = CandidaturaCampoAprobacion::crearOActualizar(
            $candidatura,
            $campoId,
            false, // rechazado
            Auth::user(),
            $request->comentario,
            $valorActual
        );

        return response()->json([
            'success' => true,
            'message' => 'Campo rechazado correctamente',
            'aprobacion' => [
                'campo_id' => $aprobacion->campo_id,
                'aprobado' => $aprobacion->aprobado,
                'estado_label' => $aprobacion->estado_label,
                'comentario' => $aprobacion->comentario,
                'aprobado_por' => [
                    'id' => Auth::user()->id,
                    'name' => Auth::user()->name,
                ],
                'fecha_aprobacion' => $aprobacion->fecha_aprobacion,
            ],
        ]);
    }

    /**
     * Obtener el estado de aprobación de todos los campos
     */
    public function getEstadoAprobacionCampos(Candidatura $candidatura)
    {
        $campoAprobaciones = $candidatura->getCamposAprobaciones();
        $resumen = $candidatura->getEstadoAprobacionCampos();

        return response()->json([
            'aprobaciones' => $campoAprobaciones->map(function ($aprobacion) {
                return [
                    'campo_id' => $aprobacion->campo_id,
                    'aprobado' => $aprobacion->aprobado,
                    'estado_label' => $aprobacion->estado_label,
                    'comentario' => $aprobacion->comentario,
                    'aprobado_por' => $aprobacion->aprobadoPor ? [
                        'name' => $aprobacion->aprobadoPor->name,
                        'email' => $aprobacion->aprobadoPor->email,
                    ] : null,
                    'fecha_aprobacion' => $aprobacion->fecha_aprobacion,
                ];
            }),
            'resumen' => $resumen,
        ]);
    }

    /**
     * Aprobar globalmente basado en aprobaciones de campos
     */
    public function aprobarGlobal(Request $request, Candidatura $candidatura)
    {
        $request->validate([
            'comentarios' => 'nullable|string|max:1000',
            'forzar' => 'boolean', // Permitir aprobación aunque no todos los campos estén aprobados
        ]);

        // Verificar que puede ser aprobada globalmente
        if (!$request->forzar && !$candidatura->puedeSerAprobadaGlobalmente()) {
            return response()->json([
                'error' => 'No todos los campos requeridos han sido aprobados individualmente',
                'resumen' => $candidatura->getEstadoAprobacionCampos(),
            ], 422);
        }

        // Aprobar la candidatura
        $candidatura->aprobar(Auth::user(), $request->comentarios);

        return response()->json([
            'success' => true,
            'message' => 'Candidatura aprobada globalmente',
        ]);
    }
}
