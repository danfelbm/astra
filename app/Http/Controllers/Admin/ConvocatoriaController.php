<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Convocatoria;
use App\Models\Cargo;
use App\Models\PeriodoElectoral;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ConvocatoriaController extends Controller
{
    use HasAdvancedFilters;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Convocatoria::with(['cargo', 'periodoElectoral', 'territorio', 'departamento', 'municipio', 'localidad']);

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'nombre', 'descripcion', 'fecha_apertura', 'fecha_cierre',
            'cargo_id', 'periodo_electoral_id', 'estado', 'activo',
            'territorio_id', 'departamento_id', 'municipio_id', 'localidad_id',
            'created_at', 'updated_at'
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['nombre', 'descripcion'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Mantener compatibilidad con filtros simples existentes
        $this->applySimpleFilters($query, $request);

        // Ordenamiento
        $sortBy = $request->get('sort', 'fecha_apertura');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $convocatorias = $query->paginate(15)->withQueryString();

        // Enriquecer datos con información de estado para el frontend
        $convocatorias->getCollection()->transform(function ($convocatoria) {
            return [
                'id' => $convocatoria->id,
                'nombre' => $convocatoria->nombre,
                'descripcion' => $convocatoria->descripcion,
                'fecha_apertura' => $convocatoria->fecha_apertura,
                'fecha_cierre' => $convocatoria->fecha_cierre,
                'estado' => $convocatoria->estado,
                'activo' => $convocatoria->activo,
                'created_at' => $convocatoria->created_at,
                'cargo' => $convocatoria->cargo ? [
                    'id' => $convocatoria->cargo->id,
                    'nombre' => $convocatoria->cargo->nombre,
                    'ruta_jerarquica' => $convocatoria->cargo->getRutaJerarquica(),
                ] : null,
                'periodo_electoral' => $convocatoria->periodoElectoral ? [
                    'id' => $convocatoria->periodoElectoral->id,
                    'nombre' => $convocatoria->periodoElectoral->nombre,
                ] : null,
                'estado_temporal' => $convocatoria->getEstadoTemporal(),
                'estado_temporal_label' => $convocatoria->getEstadoTemporalLabel(),
                'estado_temporal_color' => $convocatoria->getEstadoTemporalColor(),
                'duracion' => $convocatoria->getDuracion(),
                'dias_restantes' => $convocatoria->getDiasRestantes(),
                'rango_fechas' => $convocatoria->getRangoFechas(),
                'ubicacion_texto' => $convocatoria->getUbicacionTexto(),
            ];
        });

        return Inertia::render('Admin/Convocatorias/Index', [
            'convocatorias' => $convocatorias,
            'filters' => $request->only(['estado_temporal', 'estado', 'activo', 'cargo_id', 'periodo_electoral_id', 'search', 'advanced_filters']),
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
            // Filtro por estado temporal
            if ($request->filled('estado_temporal')) {
                switch ($request->estado_temporal) {
                    case 'abierta':
                        $query->abiertas();
                        break;
                    case 'futura':
                        $query->futuras();
                        break;
                    case 'cerrada':
                        $query->cerradas();
                        break;
                }
            }

            // Filtro por estado
            if ($request->filled('estado')) {
                $query->porEstado($request->estado);
            }

            // Filtro por activo
            if ($request->filled('activo')) {
                $query->where('activo', $request->activo === '1');
            }

            // Filtro por cargo
            if ($request->filled('cargo_id')) {
                $query->where('cargo_id', $request->cargo_id);
            }

            // Filtro por periodo electoral
            if ($request->filled('periodo_electoral_id')) {
                $query->where('periodo_electoral_id', $request->periodo_electoral_id);
            }
        }
    }
    
    /**
     * Obtener configuración de campos para filtros avanzados
     */
    public function getFilterFieldsConfig(): array
    {
        // Cargar cargos para el select
        $cargos = Cargo::activos()->soloCargos()->get()->map(fn($c) => [
            'value' => $c->id,
            'label' => $c->getRutaJerarquica()
        ]);
        
        // Cargar periodos electorales
        $periodos = PeriodoElectoral::activos()->get()->map(fn($p) => [
            'value' => $p->id,
            'label' => $p->nombre
        ]);
        
        // Cargar ubicaciones geográficas
        $territorios = \App\Models\Territorio::all()->map(fn($t) => [
            'value' => $t->id,
            'label' => $t->nombre
        ]);
        
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
                'name' => 'fecha_apertura',
                'label' => 'Fecha de Apertura',
                'type' => 'datetime',
            ],
            [
                'name' => 'fecha_cierre',
                'label' => 'Fecha de Cierre',
                'type' => 'datetime',
            ],
            [
                'name' => 'cargo_id',
                'label' => 'Cargo',
                'type' => 'select',
                'options' => $cargos->toArray(),
            ],
            [
                'name' => 'periodo_electoral_id',
                'label' => 'Periodo Electoral',
                'type' => 'select',
                'options' => $periodos->toArray(),
            ],
            [
                'name' => 'estado',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'activa', 'label' => 'Activa'],
                    ['value' => 'borrador', 'label' => 'Borrador'],
                    ['value' => 'finalizada', 'label' => 'Finalizada'],
                ],
            ],
            [
                'name' => 'activo',
                'label' => 'Activo',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Sí'],
                    ['value' => 0, 'label' => 'No'],
                ],
            ],
            [
                'name' => 'territorio_id',
                'label' => 'Territorio',
                'type' => 'select',
                'options' => $territorios->toArray(),
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
        return Inertia::render('Admin/Convocatorias/Form', [
            'convocatoria' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_apertura' => 'required|date',
            'fecha_cierre' => 'required|date|after:fecha_apertura',
            'cargo_id' => 'required|exists:cargos,id',
            'periodo_electoral_id' => 'required|exists:periodos_electorales,id',
            'territorio_id' => 'nullable|integer',
            'departamento_id' => 'nullable|integer',
            'municipio_id' => 'nullable|integer',
            'localidad_id' => 'nullable|integer',
            'formulario_postulacion' => 'nullable|array',
            'estado' => 'required|in:borrador,activa,cerrada',
            'activo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre es requerido.',
            'fecha_apertura.required' => 'La fecha de apertura es requerida.',
            'fecha_cierre.required' => 'La fecha de cierre es requerida.',
            'fecha_cierre.after' => 'La fecha de cierre debe ser posterior a la fecha de apertura.',
            'cargo_id.required' => 'El cargo es requerido.',
            'cargo_id.exists' => 'El cargo seleccionado no existe.',
            'periodo_electoral_id.required' => 'El periodo electoral es requerido.',
            'periodo_electoral_id.exists' => 'El periodo electoral seleccionado no existe.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Validar que las fechas estén dentro del periodo electoral
        $periodo = PeriodoElectoral::find($data['periodo_electoral_id']);
        if ($periodo) {
            $fechaApertura = new \Carbon\Carbon($data['fecha_apertura']);
            $fechaCierre = new \Carbon\Carbon($data['fecha_cierre']);
            
            if (!$periodo->estaEnRango($fechaApertura) || !$periodo->estaEnRango($fechaCierre)) {
                return back()->withErrors([
                    'fecha_apertura' => 'Las fechas de la convocatoria deben estar dentro del periodo electoral seleccionado.',
                ])->withInput();
            }
        }

        // Validar que el cargo sea realmente un cargo (no una categoría)
        $cargo = Cargo::find($data['cargo_id']);
        if ($cargo && !$cargo->es_cargo) {
            return back()->withErrors([
                'cargo_id' => 'Solo se pueden seleccionar cargos, no categorías.',
            ])->withInput();
        }

        Convocatoria::create($data);

        return redirect()->route('admin.convocatorias.index')
            ->with('success', 'Convocatoria creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Convocatoria $convocatoria): Response
    {
        $convocatoria->load(['cargo', 'periodoElectoral', 'territorio', 'departamento', 'municipio', 'localidad']);
        
        // Cargar postulaciones con sus relaciones
        $postulaciones = $convocatoria->postulaciones()
            ->with(['user', 'revisadoPor'])
            ->get()
            ->map(function ($postulacion) {
                return [
                    'id' => $postulacion->id,
                    'usuario' => [
                        'id' => $postulacion->user->id,
                        'name' => $postulacion->user->name,
                        'email' => $postulacion->user->email,
                    ],
                    'estado' => $postulacion->estado,
                    'estado_label' => $postulacion->estado_label,
                    'estado_color' => $postulacion->estado_color,
                    'fecha_postulacion' => $postulacion->fecha_postulacion ? $postulacion->fecha_postulacion->format('Y-m-d H:i:s') : null,
                    'tiene_candidatura_vinculada' => $postulacion->tieneCandidaturaVinculada(),
                    'revisado_por' => $postulacion->revisadoPor ? [
                        'name' => $postulacion->revisadoPor->name,
                        'email' => $postulacion->revisadoPor->email,
                    ] : null,
                    'fecha_revision' => $postulacion->revisado_at ? $postulacion->revisado_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $postulacion->created_at->format('Y-m-d H:i:s'),
                ];
            });
        
        return Inertia::render('Admin/Convocatorias/Show', [
            'convocatoria' => $convocatoria,
            'postulaciones' => $postulaciones,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Convocatoria $convocatoria): Response
    {
        return Inertia::render('Admin/Convocatorias/Form', [
            'convocatoria' => $convocatoria,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Convocatoria $convocatoria): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_apertura' => 'required|date',
            'fecha_cierre' => 'required|date|after:fecha_apertura',
            'cargo_id' => 'required|exists:cargos,id',
            'periodo_electoral_id' => 'required|exists:periodos_electorales,id',
            'territorio_id' => 'nullable|integer',
            'departamento_id' => 'nullable|integer',
            'municipio_id' => 'nullable|integer',
            'localidad_id' => 'nullable|integer',
            'formulario_postulacion' => 'nullable|array',
            'estado' => 'required|in:borrador,activa,cerrada',
            'activo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre es requerido.',
            'fecha_apertura.required' => 'La fecha de apertura es requerida.',
            'fecha_cierre.required' => 'La fecha de cierre es requerida.',
            'fecha_cierre.after' => 'La fecha de cierre debe ser posterior a la fecha de apertura.',
            'cargo_id.required' => 'El cargo es requerido.',
            'cargo_id.exists' => 'El cargo seleccionado no existe.',
            'periodo_electoral_id.required' => 'El periodo electoral es requerido.',
            'periodo_electoral_id.exists' => 'El periodo electoral seleccionado no existe.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Validar que las fechas estén dentro del periodo electoral
        $periodo = PeriodoElectoral::find($data['periodo_electoral_id']);
        if ($periodo) {
            $fechaApertura = new \Carbon\Carbon($data['fecha_apertura']);
            $fechaCierre = new \Carbon\Carbon($data['fecha_cierre']);
            
            if (!$periodo->estaEnRango($fechaApertura) || !$periodo->estaEnRango($fechaCierre)) {
                return back()->withErrors([
                    'fecha_apertura' => 'Las fechas de la convocatoria deben estar dentro del periodo electoral seleccionado.',
                ])->withInput();
            }
        }

        // Validar que el cargo sea realmente un cargo (no una categoría)
        $cargo = Cargo::find($data['cargo_id']);
        if ($cargo && !$cargo->es_cargo) {
            return back()->withErrors([
                'cargo_id' => 'Solo se pueden seleccionar cargos, no categorías.',
            ])->withInput();
        }

        // TODO: Validar que no tenga postulaciones activas si se están cambiando campos críticos
        // cuando se implemente el módulo de postulaciones

        $convocatoria->update($data);

        return redirect()->route('admin.convocatorias.index')
            ->with('success', 'Convocatoria actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Convocatoria $convocatoria): RedirectResponse
    {
        // TODO: Verificar que no tenga postulaciones asociadas cuando se implemente el módulo de postulaciones
        // if ($convocatoria->postulaciones()->exists()) {
        //     return back()->withErrors(['delete' => 'No se puede eliminar una convocatoria que tiene postulaciones asociadas.']);
        // }

        $convocatoria->delete();

        return redirect()->route('admin.convocatorias.index')
            ->with('success', 'Convocatoria eliminada exitosamente.');
    }

    /**
     * API endpoint para obtener convocatorias disponibles (abiertas y futuras)
     */
    public function getConvocatoriasDisponibles(): JsonResponse
    {
        $convocatorias = Convocatoria::disponibles()
            ->with(['cargo', 'periodoElectoral', 'territorio', 'departamento', 'municipio', 'localidad'])
            ->ordenadosPorFecha()
            ->get()
            ->map(function ($convocatoria) {
                return [
                    'id' => $convocatoria->id,
                    'nombre' => $convocatoria->nombre,
                    'descripcion' => $convocatoria->descripcion,
                    'fecha_apertura' => $convocatoria->fecha_apertura,
                    'fecha_cierre' => $convocatoria->fecha_cierre,
                    'cargo' => [
                        'id' => $convocatoria->cargo->id,
                        'nombre' => $convocatoria->cargo->nombre,
                        'ruta_jerarquica' => $convocatoria->cargo->getRutaJerarquica(),
                    ],
                    'periodo_electoral' => [
                        'id' => $convocatoria->periodoElectoral->id,
                        'nombre' => $convocatoria->periodoElectoral->nombre,
                    ],
                    'estado_temporal' => $convocatoria->getEstadoTemporal(),
                    'estado_temporal_label' => $convocatoria->getEstadoTemporalLabel(),
                    'duracion' => $convocatoria->getDuracion(),
                    'rango_fechas' => $convocatoria->getRangoFechas(),
                    'ubicacion_texto' => $convocatoria->getUbicacionTexto(),
                ];
            });

        return response()->json($convocatorias);
    }

    /**
     * API endpoint para obtener convocatorias por estado específico
     */
    public function getConvocatoriasPorEstado(Request $request, string $estado): JsonResponse
    {
        $query = Convocatoria::activas()->with(['cargo', 'periodoElectoral', 'territorio', 'departamento', 'municipio', 'localidad']);

        switch ($estado) {
            case 'abierta':
                $query->abiertas();
                break;
            case 'futura':
                $query->futuras();
                break;
            case 'cerrada':
                $query->cerradas();
                break;
            default:
                return response()->json(['error' => 'Estado no válido'], 400);
        }

        $convocatorias = $query->ordenadosPorFecha()
            ->get()
            ->map(function ($convocatoria) {
                return [
                    'id' => $convocatoria->id,
                    'nombre' => $convocatoria->nombre,
                    'descripcion' => $convocatoria->descripcion,
                    'cargo' => [
                        'nombre' => $convocatoria->cargo->nombre,
                        'ruta_jerarquica' => $convocatoria->cargo->getRutaJerarquica(),
                    ],
                    'estado_temporal_label' => $convocatoria->getEstadoTemporalLabel(),
                    'rango_fechas' => $convocatoria->getRangoFechas(),
                ];
            });

        return response()->json($convocatorias);
    }
}
