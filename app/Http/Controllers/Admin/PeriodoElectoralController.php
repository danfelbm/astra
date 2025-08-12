<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodoElectoral;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class PeriodoElectoralController extends Controller
{
    use HasAdvancedFilters;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = PeriodoElectoral::query();

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'nombre', 'descripcion', 'fecha_inicio', 'fecha_fin', 'activo',
            'created_at', 'updated_at'
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['nombre', 'descripcion'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Mantener compatibilidad con filtros simples existentes
        $this->applySimpleFilters($query, $request);

        $periodos = $query->ordenadosCronologicamente()
            ->paginate(15)
            ->withQueryString();

        // Enriquecer datos con información de estado para el frontend
        $periodos->getCollection()->transform(function ($periodo) {
            return [
                'id' => $periodo->id,
                'nombre' => $periodo->nombre,
                'descripcion' => $periodo->descripcion,
                'fecha_inicio' => $periodo->fecha_inicio,
                'fecha_fin' => $periodo->fecha_fin,
                'activo' => $periodo->activo,
                'created_at' => $periodo->created_at,
                'estado' => $periodo->getEstado(),
                'estado_label' => $periodo->getEstadoLabel(),
                'estado_color' => $periodo->getEstadoColor(),
                'duracion' => $periodo->getDuracion(),
                'dias_restantes' => $periodo->getDiasRestantes(),
                'rango_fechas' => $periodo->getRangoFechas(),
            ];
        });

        return Inertia::render('Admin/PeriodosElectorales/Index', [
            'periodos' => $periodos,
            'filters' => $request->only(['estado', 'activo', 'search', 'advanced_filters']),
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
            if ($request->filled('estado')) {
                switch ($request->estado) {
                    case 'vigente':
                        $query->vigentes();
                        break;
                    case 'futuro':
                        $query->futuros();
                        break;
                    case 'pasado':
                        $query->pasados();
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
                'name' => 'activo',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Activo'],
                    ['value' => 0, 'label' => 'Inactivo'],
                ],
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
        return Inertia::render('Admin/PeriodosElectorales/Form', [
            'periodo' => null,
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
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'activo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre es requerido.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'fecha_fin.required' => 'La fecha de fin es requerida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        PeriodoElectoral::create($data);

        return redirect()->route('admin.periodos-electorales.index')
            ->with('success', 'Periodo electoral creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PeriodoElectoral $periodosElectorale): Response
    {
        return Inertia::render('Admin/PeriodosElectorales/Form', [
            'periodo' => $periodosElectorale,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PeriodoElectoral $periodosElectorale): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'activo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre es requerido.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'fecha_fin.required' => 'La fecha de fin es requerida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $periodosElectorale->update($data);

        return redirect()->route('admin.periodos-electorales.index')
            ->with('success', 'Periodo electoral actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PeriodoElectoral $periodosElectorale): RedirectResponse
    {
        // TODO: Verificar que no tenga postulaciones asociadas cuando se implemente el módulo de convocatorias
        // if ($periodosElectorale->postulaciones()->exists()) {
        //     return back()->withErrors(['delete' => 'No se puede eliminar un periodo que tiene postulaciones asociadas.']);
        // }

        $periodosElectorale->delete();

        return redirect()->route('admin.periodos-electorales.index')
            ->with('success', 'Periodo electoral eliminado exitosamente.');
    }

    /**
     * API endpoint para obtener periodos disponibles (vigentes y futuros)
     */
    public function getPeriodosDisponibles(): JsonResponse
    {
        $periodos = PeriodoElectoral::disponibles()
            ->ordenadosCronologicamente()
            ->get()
            ->map(function ($periodo) {
                return [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre,
                    'descripcion' => $periodo->descripcion,
                    'fecha_inicio' => $periodo->fecha_inicio,
                    'fecha_fin' => $periodo->fecha_fin,
                    'estado' => $periodo->getEstado(),
                    'estado_label' => $periodo->getEstadoLabel(),
                    'duracion' => $periodo->getDuracion(),
                    'rango_fechas' => $periodo->getRangoFechas(),
                ];
            });

        return response()->json($periodos);
    }

    /**
     * API endpoint para obtener periodos por estado específico
     */
    public function getPeriodosPorEstado(Request $request, string $estado): JsonResponse
    {
        $query = PeriodoElectoral::activos();

        switch ($estado) {
            case 'vigente':
                $query->vigentes();
                break;
            case 'futuro':
                $query->futuros();
                break;
            case 'pasado':
                $query->pasados();
                break;
            default:
                return response()->json(['error' => 'Estado no válido'], 400);
        }

        $periodos = $query->ordenadosCronologicamente()
            ->get()
            ->map(function ($periodo) {
                return [
                    'id' => $periodo->id,
                    'nombre' => $periodo->nombre,
                    'descripcion' => $periodo->descripcion,
                    'estado_label' => $periodo->getEstadoLabel(),
                    'rango_fechas' => $periodo->getRangoFechas(),
                ];
            });

        return response()->json($periodos);
    }
}
