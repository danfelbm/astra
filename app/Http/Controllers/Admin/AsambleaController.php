<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asamblea;
use App\Models\User;
use App\Models\Territorio;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Localidad;
use App\Services\ZoomService;
use App\Traits\HasAdvancedFilters;
use App\Traits\HasGeographicFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class AsambleaController extends Controller
{
    use HasAdvancedFilters, HasGeographicFilters;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('asambleas.view')) {
            abort(403, 'No tienes permisos para ver asambleas');
        }

        $query = Asamblea::with(['territorio', 'departamento', 'municipio', 'localidad'])
            ->withCount('participantes');

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'nombre', 'descripcion', 'tipo', 'estado', 'lugar',
            'fecha_inicio', 'fecha_fin', 'territorio_id', 'departamento_id', 
            'municipio_id', 'localidad_id', 'activo', 'quorum_minimo',
            'zoom_enabled', 'zoom_meeting_id', 'zoom_meeting_type',
            'created_at', 'updated_at', 'participantes_count'
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['nombre', 'descripcion', 'lugar'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Mantener compatibilidad con filtros simples existentes
        $this->applySimpleFilters($query, $request);

        $asambleas = $query->ordenadoPorFecha()
            ->paginate(15)
            ->withQueryString();

        // Enriquecer datos con información de estado para el frontend
        $asambleas->getCollection()->transform(function ($asamblea) {
            return [
                'id' => $asamblea->id,
                'nombre' => $asamblea->nombre,
                'descripcion' => $asamblea->descripcion,
                'tipo' => $asamblea->tipo,
                'tipo_label' => $asamblea->getTipoLabel(),
                'estado' => $asamblea->estado,
                'estado_label' => $asamblea->getEstadoLabel(),
                'estado_color' => $asamblea->getEstadoColor(),
                'fecha_inicio' => $asamblea->fecha_inicio,
                'fecha_fin' => $asamblea->fecha_fin,
                'lugar' => $asamblea->lugar,
                'territorio' => $asamblea->territorio,
                'departamento' => $asamblea->departamento,
                'municipio' => $asamblea->municipio,
                'localidad' => $asamblea->localidad,
                'ubicacion_completa' => $asamblea->getUbicacionCompleta(),
                'quorum_minimo' => $asamblea->quorum_minimo,
                'activo' => $asamblea->activo,
                'acta_url' => $asamblea->acta_url,
                'created_at' => $asamblea->created_at,
                'estado_temporal' => $asamblea->getEstadoTemporal(),
                'duracion' => $asamblea->getDuracion(),
                'tiempo_restante' => $asamblea->getTiempoRestante(),
                'rango_fechas' => $asamblea->getRangoFechas(),
                'participantes_count' => $asamblea->participantes_count,
            ];
        });

        return Inertia::render('Admin/Asambleas/Index', [
            'asambleas' => $asambleas,
            'filters' => $request->only(['tipo', 'estado', 'activo', 'search', 'advanced_filters']),
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
            // Filtro por tipo
            if ($request->filled('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            // Filtro por estado
            if ($request->filled('estado')) {
                switch ($request->estado) {
                    case 'programada':
                        $query->programadas();
                        break;
                    case 'en_curso':
                        $query->enCurso();
                        break;
                    case 'finalizada':
                        $query->finalizadas();
                        break;
                    case 'cancelada':
                        $query->canceladas();
                        break;
                }
            }

            // Filtro por estado activo
            if ($request->filled('activo')) {
                $query->where('activo', $request->activo === '1');
            }
        }
    }
    
    /**
     * Obtener configuración de campos para filtros avanzados
     */
    public function getFilterFieldsConfig(): array
    {
        return [
            [
                'name' => 'nombre',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            [
                'name' => 'descripcion',
                'label' => 'Descripción',
                'type' => 'text',
            ],
            [
                'name' => 'tipo',
                'label' => 'Tipo',
                'type' => 'select',
                'options' => [
                    ['value' => 'ordinaria', 'label' => 'Ordinaria'],
                    ['value' => 'extraordinaria', 'label' => 'Extraordinaria'],
                ],
            ],
            [
                'name' => 'estado',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'programada', 'label' => 'Programada'],
                    ['value' => 'en_curso', 'label' => 'En Curso'],
                    ['value' => 'finalizada', 'label' => 'Finalizada'],
                    ['value' => 'cancelada', 'label' => 'Cancelada'],
                ],
            ],
            [
                'name' => 'lugar',
                'label' => 'Lugar',
                'type' => 'text',
            ],
            [
                'name' => 'fecha_inicio',
                'label' => 'Fecha de Inicio',
                'type' => 'datetime',
            ],
            [
                'name' => 'fecha_fin',
                'label' => 'Fecha de Fin',
                'type' => 'datetime',
            ],
            // Campos geográficos en cascada usando el trait
            ...$this->getGeographicFilterFields(),
            [
                'name' => 'quorum_minimo',
                'label' => 'Quórum Mínimo',
                'type' => 'number',
            ],
            [
                'name' => 'activo',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Activo'],
                    ['value' => 0, 'label' => 'Inactivo'],
                ],
            ],
            [
                'name' => 'participantes_count',
                'label' => 'Cantidad de Participantes',
                'type' => 'number',
                'operators' => ['equals', 'not_equals', 'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal', 'between'],
            ],
            [
                'name' => 'created_at',
                'label' => 'Fecha de Creación',
                'type' => 'datetime',
            ],
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('asambleas.create')) {
            abort(403, 'No tienes permisos para crear asambleas');
        }

        return Inertia::render('Admin/Asambleas/Form', [
            'asamblea' => null,
            'territorios' => Territorio::all(),
            'departamentos' => Departamento::all(),
            'municipios' => Municipio::all(),
            'localidades' => Localidad::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('asambleas.create')) {
            abort(403, 'No tienes permisos para crear asambleas');
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:ordinaria,extraordinaria',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'territorio_id' => 'nullable|exists:territorios,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'localidad_id' => 'nullable|exists:localidades,id',
            'lugar' => 'nullable|string|max:255',
            'quorum_minimo' => 'nullable|integer|min:1',
            'activo' => 'boolean',
            // Campos de Zoom
            'zoom_enabled' => 'boolean',
            'zoom_integration_type' => 'nullable|in:sdk,api',
            'zoom_meeting_type' => 'nullable|in:instant,scheduled,recurring',
            'zoom_settings' => 'nullable|array',
            'zoom_settings.host_video' => 'nullable|boolean',
            'zoom_settings.participant_video' => 'nullable|boolean',
            'zoom_settings.waiting_room' => 'nullable|boolean',
            'zoom_settings.mute_upon_entry' => 'nullable|boolean',
            'zoom_settings.auto_recording' => 'nullable|in:none,local,cloud',
            // Campos específicos para modo API
            'zoom_meeting_id' => 'nullable|string|max:255',
            'zoom_meeting_password' => 'nullable|string|max:255',
            'zoom_occurrence_ids' => 'nullable|string|max:500',
            'zoom_prefix' => 'nullable|string|max:10',
            'zoom_registration_open_date' => 'nullable|date',
            'zoom_join_url' => 'nullable|string|max:500',
            'zoom_start_url' => 'nullable|string|max:500',
        ], [
            'nombre.required' => 'El nombre es requerido.',
            'tipo.required' => 'El tipo de asamblea es requerido.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'fecha_fin.required' => 'La fecha de fin es requerida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'quorum_minimo.min' => 'El quórum mínimo debe ser al menos 1.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['estado'] = 'programada'; // Estado inicial
        
        // DEBUG: Log para verificar datos que llegan (comentado)
        // \Log::info('Datos de Asamblea STORE recibidos:', [
        //     'zoom_enabled' => $data['zoom_enabled'] ?? 'NOT_SET',
        //     'zoom_integration_type' => $data['zoom_integration_type'] ?? 'NOT_SET',
        //     'zoom_meeting_id' => $data['zoom_meeting_id'] ?? 'NOT_SET',
        //     'zoom_occurrence_ids' => $data['zoom_occurrence_ids'] ?? 'NOT_SET',
        //     'zoom_join_url' => $data['zoom_join_url'] ?? 'NOT_SET',
        //     'zoom_start_url' => $data['zoom_start_url'] ?? 'NOT_SET',
        // ]);
        
        // Establecer valor por defecto para zoom_integration_type si está habilitado
        if (($data['zoom_enabled'] ?? false) && empty($data['zoom_integration_type'])) {
            $data['zoom_integration_type'] = 'sdk';
        }

        $asamblea = Asamblea::create($data);

        // Crear reunión de Zoom solo si está habilitado y es modo SDK
        if (($data['zoom_enabled'] ?? false) && ($data['zoom_integration_type'] ?? 'sdk') === 'sdk') {
            $zoomService = new ZoomService();
            $result = $zoomService->createMeeting($asamblea);
            
            if (!$result['success']) {
                // Si falla la creación de Zoom, deshabilitar pero no fallar la creación de la asamblea
                $asamblea->update(['zoom_enabled' => false]);
                return redirect()->route('admin.asambleas.index')
                    ->with('warning', 'Asamblea creada exitosamente, pero no se pudo crear la reunión de Zoom: ' . $result['message']);
            }
        }

        return redirect()->route('admin.asambleas.index')
            ->with('success', 'Asamblea creada exitosamente.' . ($data['zoom_enabled'] ?? false ? ' Reunión de Zoom configurada.' : ''));
    }

    /**
     * Display the specified resource.
     */
    public function show(Asamblea $asamblea): Response
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('asambleas.view')) {
            abort(403, 'No tienes permisos para ver asambleas');
        }

        $asamblea->load([
            'territorio', 
            'departamento', 
            'municipio', 
            'localidad'
        ]);

        return Inertia::render('Admin/Asambleas/Show', [
            'asamblea' => array_merge($asamblea->toArray(), [
                'estado_label' => $asamblea->getEstadoLabel(),
                'estado_color' => $asamblea->getEstadoColor(),
                'tipo_label' => $asamblea->getTipoLabel(),
                'ubicacion_completa' => $asamblea->getUbicacionCompleta(),
                'duracion' => $asamblea->getDuracion(),
                'tiempo_restante' => $asamblea->getTiempoRestante(),
                'rango_fechas' => $asamblea->getRangoFechas(),
                'alcanza_quorum' => $asamblea->alcanzaQuorum(),
                'asistentes_count' => $asamblea->getAsistentesCount(),
                'participantes_count' => $asamblea->getParticipantesCount(),
            ]),
            'puede_gestionar_participantes' => Auth::user()->hasPermission('asambleas.manage_participants'),
            'filterFieldsConfig' => $this->getParticipantesFilterFieldsConfig(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Asamblea $asamblea): Response
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('asambleas.edit')) {
            abort(403, 'No tienes permisos para editar asambleas');
        }

        return Inertia::render('Admin/Asambleas/Form', [
            'asamblea' => $asamblea,
            'territorios' => Territorio::all(),
            'departamentos' => Departamento::all(),
            'municipios' => Municipio::all(),
            'localidades' => Localidad::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Asamblea $asamblea): RedirectResponse
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('asambleas.edit')) {
            abort(403, 'No tienes permisos para editar asambleas');
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:ordinaria,extraordinaria',
            'estado' => 'required|in:programada,en_curso,finalizada,cancelada',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'territorio_id' => 'nullable|exists:territorios,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'localidad_id' => 'nullable|exists:localidades,id',
            'lugar' => 'nullable|string|max:255',
            'quorum_minimo' => 'nullable|integer|min:1',
            'activo' => 'boolean',
            'acta_url' => 'nullable|string|max:255',
            // Campos de Zoom
            'zoom_enabled' => 'boolean',
            'zoom_integration_type' => 'nullable|in:sdk,api',
            'zoom_meeting_type' => 'nullable|in:instant,scheduled,recurring',
            'zoom_settings' => 'nullable|array',
            'zoom_settings.host_video' => 'nullable|boolean',
            'zoom_settings.participant_video' => 'nullable|boolean',
            'zoom_settings.waiting_room' => 'nullable|boolean',
            'zoom_settings.mute_upon_entry' => 'nullable|boolean',
            'zoom_settings.auto_recording' => 'nullable|in:none,local,cloud',
            // Campos específicos para modo API
            'zoom_meeting_id' => 'nullable|string|max:255',
            'zoom_meeting_password' => 'nullable|string|max:255',
            'zoom_occurrence_ids' => 'nullable|string|max:500',
            'zoom_prefix' => 'nullable|string|max:10',
            'zoom_registration_open_date' => 'nullable|date',
            'zoom_join_url' => 'nullable|string|max:500',
            'zoom_start_url' => 'nullable|string|max:500',
        ], [
            'nombre.required' => 'El nombre es requerido.',
            'tipo.required' => 'El tipo de asamblea es requerido.',
            'estado.required' => 'El estado es requerido.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'fecha_fin.required' => 'La fecha de fin es requerida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'quorum_minimo.min' => 'El quórum mínimo debe ser al menos 1.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        
        // DEBUG: Log para verificar datos que llegan (comentado)
        // \Log::info('Datos de Asamblea UPDATE recibidos:', [
        //     'zoom_enabled' => $data['zoom_enabled'] ?? 'NOT_SET',
        //     'zoom_integration_type' => $data['zoom_integration_type'] ?? 'NOT_SET',
        //     'zoom_meeting_id' => $data['zoom_meeting_id'] ?? 'NOT_SET',
        //     'zoom_occurrence_ids' => $data['zoom_occurrence_ids'] ?? 'NOT_SET',
        //     'zoom_join_url' => $data['zoom_join_url'] ?? 'NOT_SET',
        //     'zoom_start_url' => $data['zoom_start_url'] ?? 'NOT_SET',
        // ]);
        
        // Establecer valor por defecto para zoom_integration_type si está habilitado
        if (($data['zoom_enabled'] ?? false) && empty($data['zoom_integration_type'])) {
            $data['zoom_integration_type'] = 'sdk';
        }

        // Si se está cambiando el estado a 'en_curso', verificar que haya participantes
        if ($data['estado'] === 'en_curso' && $asamblea->participantes()->count() === 0) {
            return back()->withErrors(['estado' => 'No se puede iniciar una asamblea sin participantes asignados.']);
        }

        // Manejar cambios en Zoom - solo para modo SDK
        $zoomMessages = [];
        $currentIntegrationType = $data['zoom_integration_type'] ?? $asamblea->zoom_integration_type ?? 'sdk';
        $previousIntegrationType = $asamblea->zoom_integration_type ?? 'sdk';
        
        // Solo gestionar Zoom automáticamente si es modo SDK
        if ($currentIntegrationType === 'sdk') {
            $zoomService = new ZoomService();
            
            // Si se está habilitando Zoom por primera vez
            if ($data['zoom_enabled'] && !$asamblea->zoom_enabled) {
                $result = $zoomService->createMeeting($asamblea);
                if (!$result['success']) {
                    $data['zoom_enabled'] = false;
                    $zoomMessages[] = 'No se pudo crear la reunión de Zoom: ' . $result['message'];
                } else {
                    $zoomMessages[] = 'Reunión de Zoom creada exitosamente.';
                }
            }
            // Si se está deshabilitando Zoom
            elseif (!$data['zoom_enabled'] && $asamblea->zoom_enabled) {
                $result = $zoomService->deleteMeeting($asamblea);
                if ($result['success']) {
                    $zoomMessages[] = 'Reunión de Zoom eliminada.';
                }
            }
            // Si ya tiene Zoom habilitado y se está actualizando
            elseif ($data['zoom_enabled'] && $asamblea->zoom_enabled && $previousIntegrationType === 'sdk') {
                $result = $zoomService->updateMeeting($asamblea);
                if (!$result['success']) {
                    $zoomMessages[] = 'No se pudo actualizar la reunión de Zoom: ' . $result['message'];
                } else {
                    $zoomMessages[] = 'Reunión de Zoom actualizada.';
                }
            }
        }
        // Si se cambió de SDK a API, eliminar la reunión automática de Zoom
        elseif ($currentIntegrationType === 'api' && $previousIntegrationType === 'sdk' && $asamblea->zoom_enabled) {
            $zoomService = new ZoomService();
            $result = $zoomService->deleteMeeting($asamblea);
            if ($result['success']) {
                $zoomMessages[] = 'Reunión automática de Zoom eliminada. Ahora usa configuración manual.';
            }
        }

        $asamblea->update($data);

        $message = 'Asamblea actualizada exitosamente.';
        if (!empty($zoomMessages)) {
            $message .= ' ' . implode(' ', $zoomMessages);
        }

        return redirect()->route('admin.asambleas.index')
            ->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asamblea $asamblea): RedirectResponse
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('asambleas.delete')) {
            abort(403, 'No tienes permisos para eliminar asambleas');
        }

        // Verificar que no esté en curso
        if ($asamblea->estado === 'en_curso') {
            return back()->withErrors(['delete' => 'No se puede eliminar una asamblea en curso.']);
        }

        // Verificar que no tenga acta registrada
        if ($asamblea->acta_url) {
            return back()->withErrors(['delete' => 'No se puede eliminar una asamblea con acta registrada.']);
        }

        $asamblea->delete();

        return redirect()->route('admin.asambleas.index')
            ->with('success', 'Asamblea eliminada exitosamente.');
    }

    /**
     * Obtener participantes paginados con filtros
     */
    public function getParticipantes(Request $request, Asamblea $asamblea)
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('asambleas.view')) {
            abort(403, 'No tienes permisos para ver participantes');
        }

        // Construir query manualmente con los joins necesarios
        $query = User::query()
            ->join('asamblea_usuario', 'users.id', '=', 'asamblea_usuario.usuario_id')
            ->leftJoin('users as updater', 'asamblea_usuario.updated_by', '=', 'updater.id')
            ->where('asamblea_usuario.asamblea_id', $asamblea->id);
        
        // Aplicar filtro de tenant si no es super admin
        if (!Auth::user()->isSuperAdmin()) {
            $tenantId = app(\App\Services\TenantService::class)->getCurrentTenant()?->id;
            if ($tenantId) {
                $query->where('asamblea_usuario.tenant_id', $tenantId);
            }
        }
        
        $query->select('users.*', 
                'asamblea_usuario.tipo_participacion',
                'asamblea_usuario.asistio',
                'asamblea_usuario.hora_registro',
                'asamblea_usuario.updated_by',
                'updater.name as updated_by_name',
                'asamblea_usuario.tenant_id'
            );

        // Definir campos permitidos para filtrar (con prefijos de tabla para evitar ambigüedad)
        $allowedFields = [
            'users.name', 'users.email', 'asamblea_usuario.tipo_participacion', 'asamblea_usuario.asistio',
            'users.territorio_id', 'users.departamento_id', 'users.municipio_id', 'users.localidad_id',
        ];
        
        // Campos para búsqueda rápida (con prefijos de tabla)
        $quickSearchFields = ['users.name', 'users.email'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Aplicar filtros simples si existen
        if ($request->filled('tipo_participacion') && !$request->filled('advanced_filters')) {
            $query->where('asamblea_usuario.tipo_participacion', $request->tipo_participacion);
        }
        
        if ($request->has('asistio') && !$request->filled('advanced_filters')) {
            $query->where('asamblea_usuario.asistio', $request->asistio === 'true' || $request->asistio === '1');
        }

        // Ordenamiento
        $query->orderBy('asamblea_usuario.tipo_participacion')
              ->orderBy('users.name');

        $participantes = $query->paginate(20)->withQueryString();

        return response()->json([
            'participantes' => $participantes,
            'filterFieldsConfig' => $this->getParticipantesFilterFieldsConfig(),
        ]);
    }

    /**
     * Obtener configuración de campos para filtros avanzados de participantes
     */
    protected function getParticipantesFilterFieldsConfig(): array
    {
        // Campos básicos del usuario (con prefijos de tabla para coincidencia con backend)
        $basicFields = [
            [
                'name' => 'users.name',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            [
                'name' => 'users.email',
                'label' => 'Email',
                'type' => 'text',
            ],
            [
                'name' => 'asamblea_usuario.tipo_participacion',
                'label' => 'Tipo de Participación',
                'type' => 'select',
                'options' => [
                    ['value' => 'asistente', 'label' => 'Asistente'],
                    ['value' => 'moderador', 'label' => 'Moderador'],
                    ['value' => 'secretario', 'label' => 'Secretario'],
                ],
            ],
            [
                'name' => 'asamblea_usuario.asistio',
                'label' => 'Asistencia',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Presente'],
                    ['value' => 0, 'label' => 'Ausente'],
                ],
            ],
        ];
        
        // Obtener campos geográficos en cascada para usuarios (con prefijos de tabla)
        $geographicFields = $this->getUserGeographicFilterFields();
        
        // Agregar prefijo 'users.' a los campos geográficos y actualizar referencias de cascada
        $geographicFields = array_map(function($field) {
            // Agregar prefijo al nombre del campo
            if (isset($field['name']) && !str_contains($field['name'], '.')) {
                $field['name'] = 'users.' . $field['name'];
            }
            
            // Actualizar cascadeFrom para que coincida con el nuevo nombre con prefijo
            if (isset($field['cascadeFrom']) && !str_contains($field['cascadeFrom'], '.')) {
                $field['cascadeFrom'] = 'users.' . $field['cascadeFrom'];
            }
            
            // Actualizar cascadeChildren para que coincidan con los nuevos nombres con prefijo
            if (isset($field['cascadeChildren']) && is_array($field['cascadeChildren'])) {
                $field['cascadeChildren'] = array_map(function($child) {
                    return !str_contains($child, '.') ? 'users.' . $child : $child;
                }, $field['cascadeChildren']);
            }
            
            return $field;
        }, $geographicFields);
        
        // Combinar todos los campos
        return array_merge($basicFields, $geographicFields);
    }

    /**
     * Gestionar participantes de la asamblea
     */
    public function manageParticipantes(Request $request, Asamblea $asamblea)
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('asambleas.manage_participants')) {
            abort(403, 'No tienes permisos para gestionar participantes');
        }

        if ($request->isMethod('GET')) {
            // Búsqueda de usuarios disponibles con paginación para evitar sobrecarga
            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            
            // Obtener IDs de participantes ya asignados
            $relation = Auth::user()->isSuperAdmin() ? $asamblea->allParticipantes() : $asamblea->participantes();
            $participantesAsignadosIds = $relation->pluck('usuario_id');
            
            // Buscar usuarios disponibles con paginación
            $query = User::where('activo', true)
                ->whereNotIn('id', $participantesAsignadosIds);
            
            // Aplicar búsqueda si existe (nombre, email, documento de identidad, teléfono)
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('documento_identidad', 'like', '%' . $search . '%')
                      ->orWhere('telefono', 'like', '%' . $search . '%');
                });
            }
            
            // Paginar resultados (máximo 50 por página para evitar sobrecarga)
            // Incluir campos adicionales para mostrar en el modal
            $participantesDisponibles = $query
                ->select('id', 'name', 'email', 'documento_identidad', 'telefono')
                ->orderBy('name')
                ->paginate(50);

            return response()->json([
                'participantes_disponibles' => $participantesDisponibles,
                'search' => $search,
            ]);
        }

        if ($request->isMethod('POST')) {
            // Asignar participantes
            $validator = Validator::make($request->all(), [
                'participante_ids' => 'required|array',
                'participante_ids.*' => 'exists:users,id',
                'tipo_participacion' => 'nullable|in:asistente,moderador,secretario',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator->errors());
            }

            // Obtener el tenant_id actual
            $tenantService = app(\App\Services\TenantService::class);
            $currentTenant = $tenantService->getCurrentTenant();
            $tenantId = $currentTenant ? $currentTenant->id : 1; // Default a 1 si no hay tenant
            
            // Preparar los datos para attach con tenant_id y tipo de participación
            $attachData = [];
            $tipoParticipacion = $request->tipo_participacion ?? 'asistente';
            
            foreach ($request->participante_ids as $participanteId) {
                $attachData[$participanteId] = [
                    'tenant_id' => $tenantId,
                    'tipo_participacion' => $tipoParticipacion,
                ];
            }
            
            // Usar allParticipantes si es super admin para attach
            $relation = Auth::user()->isSuperAdmin() ? $asamblea->allParticipantes() : $asamblea->participantes();
            $relation->attach($attachData);

            return back()->with('success', 'Participantes asignados exitosamente.');
        }

        if ($request->isMethod('DELETE')) {
            // Remover participante
            $validator = Validator::make($request->all(), [
                'participante_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator->errors());
            }

            // Usar allParticipantes si es super admin para detach
            $relation = Auth::user()->isSuperAdmin() ? $asamblea->allParticipantes() : $asamblea->participantes();
            $relation->detach($request->participante_id);

            return back()->with('success', 'Participante removido exitosamente.');
        }

        if ($request->isMethod('PUT')) {
            // Actualizar tipo de participación o registrar asistencia
            $validator = Validator::make($request->all(), [
                'participante_id' => 'required|exists:users,id',
                'tipo_participacion' => 'nullable|in:asistente,moderador,secretario',
                'asistio' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator->errors());
            }

            $updateData = [];
            
            if ($request->has('tipo_participacion')) {
                $updateData['tipo_participacion'] = $request->tipo_participacion;
            }
            
            if ($request->has('asistio')) {
                $updateData['asistio'] = $request->asistio;
                if ($request->asistio) {
                    $updateData['hora_registro'] = now();
                }
                $updateData['updated_by'] = Auth::user()->id;  // Admin que registra
            }

            // Usar allParticipantes si es super admin para updateExistingPivot
            $relation = Auth::user()->isSuperAdmin() ? $asamblea->allParticipantes() : $asamblea->participantes();
            $relation->updateExistingPivot($request->participante_id, $updateData);

            return back()->with('success', 'Participante actualizado exitosamente.');
        }
    }
}