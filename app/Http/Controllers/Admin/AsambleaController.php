<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asamblea;
use App\Models\User;
use App\Models\Territorio;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Localidad;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class AsambleaController extends Controller
{
    use HasAdvancedFilters;

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
            [
                'name' => 'territorio_id',
                'label' => 'Territorio',
                'type' => 'select',
                'options' => Territorio::all()->map(fn($t) => ['value' => $t->id, 'label' => $t->nombre])->toArray(),
            ],
            [
                'name' => 'departamento_id',
                'label' => 'Departamento',
                'type' => 'select',
                'options' => Departamento::all()->map(fn($d) => ['value' => $d->id, 'label' => $d->nombre])->toArray(),
            ],
            [
                'name' => 'municipio_id',
                'label' => 'Municipio',
                'type' => 'select',
                'options' => Municipio::all()->map(fn($m) => ['value' => $m->id, 'label' => $m->nombre])->toArray(),
            ],
            [
                'name' => 'localidad_id',
                'label' => 'Localidad',
                'type' => 'select',
                'options' => Localidad::all()->map(fn($l) => ['value' => $l->id, 'label' => $l->nombre])->toArray(),
            ],
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

        Asamblea::create($data);

        return redirect()->route('admin.asambleas.index')
            ->with('success', 'Asamblea creada exitosamente.');
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
            'localidad',
            'participantes' => function($query) {
                $query->orderBy('asamblea_usuario.tipo_participacion')
                      ->orderBy('users.name');
            }
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

        // Si se está cambiando el estado a 'en_curso', verificar que haya participantes
        if ($data['estado'] === 'en_curso' && $asamblea->participantes()->count() === 0) {
            return back()->withErrors(['estado' => 'No se puede iniciar una asamblea sin participantes asignados.']);
        }

        $asamblea->update($data);

        return redirect()->route('admin.asambleas.index')
            ->with('success', 'Asamblea actualizada exitosamente.');
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
     * Gestionar participantes de la asamblea
     */
    public function manageParticipantes(Request $request, Asamblea $asamblea)
    {
        // Verificar permisos
        if (!Auth::user()->hasPermission('asambleas.manage_participants')) {
            abort(403, 'No tienes permisos para gestionar participantes');
        }

        if ($request->isMethod('GET')) {
            // Obtener participantes asignados y disponibles
            $participantesAsignados = $asamblea->participantes()->get();
            $participantesDisponibles = User::where('activo', true)
                ->whereNotIn('id', $participantesAsignados->pluck('id'))
                ->get();

            return response()->json([
                'participantes_asignados' => $participantesAsignados,
                'participantes_disponibles' => $participantesDisponibles,
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
            $tenantId = app(\App\Services\TenantService::class)->getCurrentTenant()->id;
            
            // Preparar los datos para attach con tenant_id y tipo de participación
            $attachData = [];
            $tipoParticipacion = $request->tipo_participacion ?? 'asistente';
            
            foreach ($request->participante_ids as $participanteId) {
                $attachData[$participanteId] = [
                    'tenant_id' => $tenantId,
                    'tipo_participacion' => $tipoParticipacion,
                ];
            }
            
            $asamblea->participantes()->attach($attachData);

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

            $asamblea->participantes()->detach($request->participante_id);

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
            }

            $asamblea->participantes()->updateExistingPivot($request->participante_id, $updateData);

            return back()->with('success', 'Participante actualizado exitosamente.');
        }
    }
}