<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCsvImport;
use App\Models\Categoria;
use App\Models\CsvImport;
use App\Models\User;
use App\Models\Votacion;
use App\Traits\HasAdvancedFilters;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VotacionController extends Controller
{
    use HasAdvancedFilters;
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Votacion::with(['categoria'])->withCount('votantes');

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'titulo', 'descripcion', 'categoria_id', 'estado',
            'fecha_inicio', 'fecha_fin', 'resultados_publicos',
            'created_at', 'updated_at', 'votantes_count'
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['titulo', 'descripcion'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Mantener compatibilidad con filtros simples existentes
        $this->applySimpleFilters($query, $request);

        // Ordenamiento
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $votaciones = $query->paginate(10)->withQueryString();

        $categorias = Categoria::activas()->get();

        return Inertia::render('Admin/Votaciones/Index', [
            'votaciones' => $votaciones,
            'categorias' => $categorias,
            'filters' => $request->only(['estado', 'categoria_id', 'search', 'advanced_filters']),
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
            // Filtro por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtro por categoría
            if ($request->filled('categoria_id')) {
                $query->where('categoria_id', $request->categoria_id);
            }
        }
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
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'borrador', 'label' => 'Borrador'],
                    ['value' => 'activa', 'label' => 'Activa'],
                    ['value' => 'finalizada', 'label' => 'Finalizada'],
                ],
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
                'name' => 'resultados_publicos',
                'label' => 'Resultados Públicos',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Sí'],
                    ['value' => 0, 'label' => 'No'],
                ],
            ],
            [
                'name' => 'votantes_count',
                'label' => 'Cantidad de Votantes',
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
        $categorias = Categoria::activas()->get();
        
        // Obtener cargos y períodos para el filtro de perfil_candidatura
        $cargos = \App\Models\Cargo::activos()
            ->soloCargos()
            ->get()
            ->map(function ($cargo) {
                return [
                    'id' => $cargo->id,
                    'nombre' => $cargo->nombre,
                    'ruta_jerarquica' => $cargo->getRutaJerarquica(),
                    'es_cargo' => $cargo->es_cargo,
                ];
            });
            
        $periodosElectorales = \App\Models\PeriodoElectoral::activos()
            ->disponibles()
            ->get()
            ->map(function ($periodo) {
                return [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre,
                    'fecha_inicio' => $periodo->fecha_inicio->toDateTimeString(),
                    'fecha_fin' => $periodo->fecha_fin->toDateTimeString(),
                ];
            });
            
        // Obtener convocatorias disponibles (activas o con postulaciones aprobadas)
        $convocatorias = \App\Models\Convocatoria::with(['cargo', 'periodoElectoral'])
            ->whereHas('postulaciones', function ($q) {
                $q->where('estado', 'aceptada');
            })
            ->get()
            ->map(function ($convocatoria) {
                return [
                    'id' => $convocatoria->id,
                    'nombre' => $convocatoria->nombre,
                    'cargo' => [
                        'id' => $convocatoria->cargo ? $convocatoria->cargo->id : null,
                        'nombre' => $convocatoria->cargo ? $convocatoria->cargo->nombre : null,
                        'ruta_jerarquica' => $convocatoria->cargo ? $convocatoria->cargo->getRutaJerarquica() : null,
                    ],
                    'periodo_electoral' => [
                        'id' => $convocatoria->periodoElectoral ? $convocatoria->periodoElectoral->id : null,
                        'nombre' => $convocatoria->periodoElectoral ? $convocatoria->periodoElectoral->nombre : null,
                    ],
                    'estado_temporal' => $convocatoria->getEstadoTemporal(),
                    'postulaciones_aprobadas' => $convocatoria->postulaciones()->where('estado', 'aceptada')->count(),
                ];
            });

        return Inertia::render('Admin/Votaciones/Form', [
            'categorias' => $categorias,
            'votacion' => null,
            'cargos' => $cargos,
            'periodosElectorales' => $periodosElectorales,
            'convocatorias' => $convocatorias,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'fecha_inicio' => 'required|date|after:now',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'estado' => ['required', Rule::in(['borrador', 'activa', 'finalizada'])],
            'resultados_publicos' => 'boolean',
            'fecha_publicacion_resultados' => 'nullable|date',
            'formulario_config' => 'required|array|min:1',
            'timezone' => 'required|string|timezone',
            'territorios_ids' => 'nullable|array',
            'territorios_ids.*' => 'exists:territorios,id',
            'departamentos_ids' => 'nullable|array',
            'departamentos_ids.*' => 'exists:departamentos,id',
            'municipios_ids' => 'nullable|array',
            'municipios_ids.*' => 'exists:municipios,id',
            'localidades_ids' => 'nullable|array',
            'localidades_ids.*' => 'exists:localidades,id',
        ], [
            'titulo.required' => 'El título es requerido.',
            'categoria_id.required' => 'La categoría es requerida.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'fecha_inicio.after' => 'La fecha de inicio debe ser posterior a la fecha actual.',
            'fecha_fin.required' => 'La fecha de fin es requerida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'formulario_config.required' => 'Debe configurar al menos un campo en el formulario.',
            'formulario_config.min' => 'Debe tener al menos un campo en el formulario.',
            'timezone.required' => 'La zona horaria es requerida.',
            'timezone.timezone' => 'La zona horaria seleccionada no es válida.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Convertir fechas de zona horaria seleccionada a UTC para almacenamiento
        $fechaInicioUtc = Carbon::parse($request->fecha_inicio, $request->timezone)->utc();
        $fechaFinUtc = Carbon::parse($request->fecha_fin, $request->timezone)->utc();
        $fechaPublicacionUtc = $request->fecha_publicacion_resultados 
            ? Carbon::parse($request->fecha_publicacion_resultados, $request->timezone)->utc() 
            : null;

        $votacion = Votacion::create([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'categoria_id' => $request->categoria_id,
            'fecha_inicio' => $fechaInicioUtc,
            'fecha_fin' => $fechaFinUtc,
            'estado' => $request->estado ?? 'borrador',
            'resultados_publicos' => $request->boolean('resultados_publicos'),
            'fecha_publicacion_resultados' => $fechaPublicacionUtc,
            'formulario_config' => $request->formulario_config,
            'timezone' => $request->timezone,
            'territorios_ids' => $request->territorios_ids ?: null,
            'departamentos_ids' => $request->departamentos_ids ?: null,
            'municipios_ids' => $request->municipios_ids ?: null,
            'localidades_ids' => $request->localidades_ids ?: null,
        ]);

        return redirect()
            ->route('admin.votaciones.index')
            ->with('success', 'Votación creada exitosamente.');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Votacion $votacione)
    {
        // No permitir editar votaciones activas o finalizadas
        if (in_array($votacione->estado, ['activa', 'finalizada'])) {
            return redirect()
                ->route('admin.votaciones.index')
                ->with('error', 'No se puede editar una votación que está activa o finalizada.');
        }

        $categorias = Categoria::activas()->get();
        $votacione->load(['votantes']);

        // Convertir fechas de UTC a zona horaria local para mostrar al usuario
        $votacionParaFrontend = $votacione->toArray();
        if ($votacione->fecha_inicio) {
            $votacionParaFrontend['fecha_inicio'] = Carbon::parse($votacione->fecha_inicio)
                ->setTimezone($votacione->timezone)
                ->format('Y-m-d\TH:i');
        }
        if ($votacione->fecha_fin) {
            $votacionParaFrontend['fecha_fin'] = Carbon::parse($votacione->fecha_fin)
                ->setTimezone($votacione->timezone)
                ->format('Y-m-d\TH:i');
        }
        if ($votacione->fecha_publicacion_resultados) {
            $votacionParaFrontend['fecha_publicacion_resultados'] = Carbon::parse($votacione->fecha_publicacion_resultados)
                ->setTimezone($votacione->timezone)
                ->format('Y-m-d\TH:i');
        }

        // Obtener cargos y períodos para el filtro de perfil_candidatura
        $cargos = \App\Models\Cargo::activos()
            ->soloCargos()
            ->get()
            ->map(function ($cargo) {
                return [
                    'id' => $cargo->id,
                    'nombre' => $cargo->nombre,
                    'ruta_jerarquica' => $cargo->getRutaJerarquica(),
                    'es_cargo' => $cargo->es_cargo,
                ];
            });
            
        $periodosElectorales = \App\Models\PeriodoElectoral::activos()
            ->disponibles()
            ->get()
            ->map(function ($periodo) {
                return [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre,
                    'fecha_inicio' => $periodo->fecha_inicio->toDateTimeString(),
                    'fecha_fin' => $periodo->fecha_fin->toDateTimeString(),
                ];
            });
            
        // Obtener convocatorias disponibles (activas o con postulaciones aprobadas)
        $convocatorias = \App\Models\Convocatoria::with(['cargo', 'periodoElectoral'])
            ->whereHas('postulaciones', function ($q) {
                $q->where('estado', 'aceptada');
            })
            ->get()
            ->map(function ($convocatoria) {
                return [
                    'id' => $convocatoria->id,
                    'nombre' => $convocatoria->nombre,
                    'cargo' => [
                        'id' => $convocatoria->cargo ? $convocatoria->cargo->id : null,
                        'nombre' => $convocatoria->cargo ? $convocatoria->cargo->nombre : null,
                        'ruta_jerarquica' => $convocatoria->cargo ? $convocatoria->cargo->getRutaJerarquica() : null,
                    ],
                    'periodo_electoral' => [
                        'id' => $convocatoria->periodoElectoral ? $convocatoria->periodoElectoral->id : null,
                        'nombre' => $convocatoria->periodoElectoral ? $convocatoria->periodoElectoral->nombre : null,
                    ],
                    'estado_temporal' => $convocatoria->getEstadoTemporal(),
                    'postulaciones_aprobadas' => $convocatoria->postulaciones()->where('estado', 'aceptada')->count(),
                ];
            });

        return Inertia::render('Admin/Votaciones/Form', [
            'categorias' => $categorias,
            'votacion' => $votacionParaFrontend,
            'cargos' => $cargos,
            'periodosElectorales' => $periodosElectorales,
            'convocatorias' => $convocatorias,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Votacion $votacione): RedirectResponse
    {
        // No permitir editar votaciones activas o finalizadas
        if (in_array($votacione->estado, ['activa', 'finalizada'])) {
            return back()->with('error', 'No se puede editar una votación que está activa o finalizada.');
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'estado' => ['required', Rule::in(['borrador', 'activa', 'finalizada'])],
            'resultados_publicos' => 'boolean',
            'fecha_publicacion_resultados' => 'nullable|date',
            'formulario_config' => 'required|array|min:1',
            'timezone' => 'required|string|timezone',
            'territorios_ids' => 'nullable|array',
            'territorios_ids.*' => 'exists:territorios,id',
            'departamentos_ids' => 'nullable|array',
            'departamentos_ids.*' => 'exists:departamentos,id',
            'municipios_ids' => 'nullable|array',
            'municipios_ids.*' => 'exists:municipios,id',
            'localidades_ids' => 'nullable|array',
            'localidades_ids.*' => 'exists:localidades,id',
        ], [
            'titulo.required' => 'El título es requerido.',
            'categoria_id.required' => 'La categoría es requerida.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'fecha_fin.required' => 'La fecha de fin es requerida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'formulario_config.required' => 'Debe configurar al menos un campo en el formulario.',
            'formulario_config.min' => 'Debe tener al menos un campo en el formulario.',
            'timezone.required' => 'La zona horaria es requerida.',
            'timezone.timezone' => 'La zona horaria seleccionada no es válida.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Convertir fechas de zona horaria seleccionada a UTC para almacenamiento
        $fechaInicioUtc = Carbon::parse($request->fecha_inicio, $request->timezone)->utc();
        $fechaFinUtc = Carbon::parse($request->fecha_fin, $request->timezone)->utc();
        $fechaPublicacionUtc = $request->fecha_publicacion_resultados 
            ? Carbon::parse($request->fecha_publicacion_resultados, $request->timezone)->utc() 
            : null;

        $votacione->update([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'categoria_id' => $request->categoria_id,
            'fecha_inicio' => $fechaInicioUtc,
            'fecha_fin' => $fechaFinUtc,
            'estado' => $request->estado,
            'resultados_publicos' => $request->boolean('resultados_publicos'),
            'fecha_publicacion_resultados' => $fechaPublicacionUtc,
            'formulario_config' => $request->formulario_config,
            'timezone' => $request->timezone,
            'territorios_ids' => $request->territorios_ids ?: null,
            'departamentos_ids' => $request->departamentos_ids ?: null,
            'municipios_ids' => $request->municipios_ids ?: null,
            'localidades_ids' => $request->localidades_ids ?: null,
        ]);

        return redirect()
            ->route('admin.votaciones.index')
            ->with('success', 'Votación actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Votacion $votacione): RedirectResponse
    {
        // No permitir eliminar votaciones activas o finalizadas
        if (in_array($votacione->estado, ['activa', 'finalizada'])) {
            return back()->with('error', 'No se puede eliminar una votación que está activa o finalizada.');
        }

        $titulo = $votacione->titulo;
        $votacione->delete();

        return redirect()
            ->route('admin.votaciones.index')
            ->with('success', "Votación '{$titulo}' eliminada exitosamente.");
    }

    /**
     * Toggle status between borrador and activa
     */
    public function toggleStatus(Request $request, Votacion $votacione): RedirectResponse
    {
        $nuevoEstado = $request->input('estado');
        
        // Validar estado válido
        if (!in_array($nuevoEstado, ['borrador', 'activa', 'finalizada'])) {
            return back()->with('error', 'Estado no válido.');
        }
        
        // Validaciones de negocio
        if ($votacione->estado === 'finalizada') {
            return back()->with('error', 'No se puede cambiar el estado de una votación finalizada.');
        }
        
        // Si se está activando, validar que tenga al menos un votante
        if ($nuevoEstado === 'activa' && $votacione->votantes()->count() === 0) {
            return back()->with('error', 'No se puede activar una votación sin votantes asignados.');
        }
        
        // Si se está activando, validar que las fechas sean coherentes
        if ($nuevoEstado === 'activa') {
            $now = $votacione->ahora();
            $fechaFin = $votacione->enZonaHoraria($votacione->fecha_fin);
            
            if ($fechaFin <= $now) {
                return back()->with('error', 'No se puede activar una votación cuya fecha de fin ya ha pasado.');
            }
        }
        
        $estadoAnterior = $votacione->estado;
        $votacione->update(['estado' => $nuevoEstado]);
        
        $mensaje = $this->getMensajeCambioEstado($estadoAnterior, $nuevoEstado, $votacione->titulo);
        
        return back()->with('success', $mensaje);
    }
    
    /**
     * Generar mensaje apropiado para el cambio de estado
     */
    private function getMensajeCambioEstado(string $estadoAnterior, string $nuevoEstado, string $titulo): string
    {
        $acciones = [
            'borrador' => [
                'activa' => "Votación '{$titulo}' activada exitosamente.",
                'finalizada' => "Votación '{$titulo}' finalizada exitosamente."
            ],
            'activa' => [
                'borrador' => "Votación '{$titulo}' desactivada y vuelve al estado borrador.",
                'finalizada' => "Votación '{$titulo}' finalizada exitosamente."
            ]
        ];
        
        return $acciones[$estadoAnterior][$nuevoEstado] ?? "Estado de votación '{$titulo}' cambiado exitosamente.";
    }

    /**
     * Manage voters for a specific votacion
     */
    public function manageVotantes(Request $request, Votacion $votacione)
    {
        if ($request->isMethod('GET')) {
            // Obtener votantes asignados y disponibles
            $votantesAsignados = $votacione->votantes()->get();
            $votantesDisponibles = User::whereDoesntHave('roles', function($query) {
                    $query->whereIn('name', ['admin', 'super_admin']);
                })
                ->where('activo', true)
                ->whereNotIn('id', $votantesAsignados->pluck('id'))
                ->get();

            return response()->json([
                'votantes_asignados' => $votantesAsignados,
                'votantes_disponibles' => $votantesDisponibles,
            ]);
        }

        if ($request->isMethod('POST')) {
            // Asignar votantes
            $validator = Validator::make($request->all(), [
                'votante_ids' => 'required|array',
                'votante_ids.*' => 'exists:users,id',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator->errors());
            }

            // Obtener el tenant_id actual
            $tenantId = app(\App\Services\TenantService::class)->getCurrentTenant()->id;
            
            // Preparar los datos para attach con tenant_id
            $attachData = [];
            foreach ($request->votante_ids as $votanteId) {
                $attachData[$votanteId] = ['tenant_id' => $tenantId];
            }
            
            $votacione->votantes()->attach($attachData);

            return back()->with('success', 'Votantes asignados exitosamente.');
        }

        if ($request->isMethod('DELETE')) {
            // Remover votante
            $validator = Validator::make($request->all(), [
                'votante_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator->errors());
            }

            $votacione->votantes()->detach($request->votante_id);

            return back()->with('success', 'Votante removido exitosamente.');
        }
    }

    /**
     * Importar votantes desde un archivo CSV (usando jobs en background)
     */
    public function importarVotantes(Request $request, Votacion $votacione): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
            'motivo' => 'required|string|max:1000',
        ], [
            'csv_file.required' => 'Debe seleccionar un archivo CSV.',
            'csv_file.mimes' => 'El archivo debe ser un CSV válido.',
            'csv_file.max' => 'El archivo no puede exceder 2MB.',
            'motivo.required' => 'Debe proporcionar un motivo para la importación.',
            'motivo.max' => 'El motivo no puede exceder 1000 caracteres.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        try {
            $file = $request->file('csv_file');
            $originalName = $file->getClientOriginalName();
            
            // Generar nombre único para el archivo
            $filename = time() . '_' . str_replace(' ', '_', $originalName);
            
            // Almacenar archivo en storage/app/imports/
            $filePath = $file->storeAs('imports', $filename);
            
            // Crear registro de importación
            $csvImport = CsvImport::create([
                'votacion_id' => $votacione->id,
                'filename' => $filename,
                'original_filename' => $originalName,
                'status' => 'pending',
                'batch_size' => config('app.csv_import_batch_size', 30),
                'motivo' => $request->input('motivo'),
                'created_by' => Auth::id(),
            ]);
            
            // Despachar job para procesar en background
            ProcessCsvImport::dispatch($csvImport);
            
            return redirect()
                ->route('admin.imports.show', $csvImport)
                ->with('success', 'Importación iniciada. El archivo se está procesando en segundo plano.');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error al iniciar importación: ' . $e->getMessage());
        }
    }
}