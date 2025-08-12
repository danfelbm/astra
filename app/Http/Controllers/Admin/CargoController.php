<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class CargoController extends Controller
{
    use HasAdvancedFilters;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Cargo::with(['parent', 'children']);

        // Definir campos permitidos para filtrar
        $allowedFields = [
            'nombre', 'descripcion', 'es_cargo', 'activo', 'parent_id',
            'created_at', 'updated_at'
        ];
        
        // Campos para búsqueda rápida
        $quickSearchFields = ['nombre', 'descripcion'];

        // Aplicar filtros avanzados
        $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        
        // Mantener compatibilidad con filtros simples existentes
        $this->applySimpleFilters($query, $request);

        $cargos = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Obtener árbol completo para visualización jerárquica
        $arbolCargos = Cargo::with(['children' => function ($query) {
            $query->orderBy('nombre');
        }])
            ->whereNull('parent_id')
            ->activos()
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Admin/Cargos/Index', [
            'cargos' => $cargos,
            'arbolCargos' => $arbolCargos,
            'filters' => $request->only(['tipo', 'activo', 'search', 'advanced_filters']),
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
            // Filtro por tipo (cargo/categoría)
            if ($request->filled('tipo')) {
                if ($request->tipo === 'cargos') {
                    $query->soloCargos();
                } elseif ($request->tipo === 'categorias') {
                    $query->soloCategories();
                }
            }

            // Filtro por estado
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
        // Cargar cargos padres para el select
        $cargosPadres = Cargo::whereNull('parent_id')->get()->map(fn($c) => [
            'value' => $c->id,
            'label' => $c->nombre
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
                'name' => 'es_cargo',
                'label' => 'Tipo',
                'type' => 'select',
                'options' => [
                    ['value' => 1, 'label' => 'Cargo'],
                    ['value' => 0, 'label' => 'Categoría'],
                ],
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
                'name' => 'parent_id',
                'label' => 'Cargo Padre',
                'type' => 'select',
                'options' => array_merge(
                    [['value' => null, 'label' => 'Sin padre (Raíz)']],
                    $cargosPadres->toArray()
                ),
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
        // Obtener solo cargos activos para el selector de padre
        $cargosPadre = Cargo::activos()
            ->orderBy('nombre')
            ->get()
            ->map(function ($cargo) {
                return [
                    'id' => $cargo->id,
                    'nombre' => $cargo->getRutaJerarquica(),
                    'es_cargo' => $cargo->es_cargo,
                ];
            });

        return Inertia::render('Admin/Cargos/Form', [
            'cargo' => null,
            'cargosPadre' => $cargosPadre,
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
            'parent_id' => 'nullable|exists:cargos,id',
            'es_cargo' => 'boolean',
            'activo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre es requerido.',
            'parent_id.exists' => 'El cargo padre seleccionado no existe.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Verificar que no se cree un ciclo en la jerarquía
        if ($data['parent_id']) {
            $padre = Cargo::find($data['parent_id']);
            if ($padre && !$padre->activo) {
                return back()->withErrors(['parent_id' => 'No se puede asignar un cargo padre inactivo.'])->withInput();
            }
        }

        Cargo::create($data);

        return redirect()->route('admin.cargos.index')
            ->with('success', 'Cargo creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cargo $cargo): Response
    {
        // Obtener cargos para el selector de padre, excluyendo el cargo actual y sus descendientes
        $cargosPadre = Cargo::activos()
            ->where('id', '!=', $cargo->id)
            ->get()
            ->filter(function ($posiblePadre) use ($cargo) {
                // Excluir descendientes para evitar ciclos
                return !$posiblePadre->esDescendienteDe($cargo);
            })
            ->map(function ($cargoItem) {
                return [
                    'id' => $cargoItem->id,
                    'nombre' => $cargoItem->getRutaJerarquica(),
                    'es_cargo' => $cargoItem->es_cargo,
                ];
            })
            ->values();

        return Inertia::render('Admin/Cargos/Form', [
            'cargo' => $cargo->load('parent'),
            'cargosPadre' => $cargosPadre,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cargo $cargo): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'parent_id' => 'nullable|exists:cargos,id',
            'es_cargo' => 'boolean',
            'activo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre es requerido.',
            'parent_id.exists' => 'El cargo padre seleccionado no existe.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Verificar que no se cree un ciclo en la jerarquía
        if ($data['parent_id']) {
            if ($data['parent_id'] == $cargo->id) {
                return back()->withErrors(['parent_id' => 'Un cargo no puede ser padre de sí mismo.'])->withInput();
            }

            $padre = Cargo::find($data['parent_id']);
            if ($padre && $padre->esDescendienteDe($cargo)) {
                return back()->withErrors(['parent_id' => 'No se puede crear un ciclo en la jerarquía.'])->withInput();
            }

            if ($padre && !$padre->activo) {
                return back()->withErrors(['parent_id' => 'No se puede asignar un cargo padre inactivo.'])->withInput();
            }
        }

        $cargo->update($data);

        return redirect()->route('admin.cargos.index')
            ->with('success', 'Cargo actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cargo $cargo): RedirectResponse
    {
        // Verificar que no tenga hijos dependientes
        if ($cargo->tieneDescendientes()) {
            return back()->withErrors(['delete' => 'No se puede eliminar un cargo que tiene cargos hijos. Elimine primero los cargos dependientes.']);
        }

        $cargo->delete();

        return redirect()->route('admin.cargos.index')
            ->with('success', 'Cargo eliminado exitosamente.');
    }

    /**
     * API endpoint para obtener el árbol jerárquico completo
     */
    public function getTree(Request $request): JsonResponse
    {
        $query = Cargo::with(['children' => function ($q) {
            $q->orderBy('nombre');
        }])
            ->whereNull('parent_id')
            ->orderBy('nombre');

        // Filtro por tipo si se especifica
        if ($request->filled('tipo')) {
            if ($request->tipo === 'cargos') {
                $query->soloCargos();
            } elseif ($request->tipo === 'categorias') {
                $query->soloCategories();
            }
        }

        // Solo activos por defecto
        if (!$request->filled('incluir_inactivos')) {
            $query->activos();
        }

        $tree = $query->get();

        return response()->json($tree);
    }

    /**
     * API endpoint para obtener cargos disponibles para convocatorias
     */
    public function getCargosForConvocatorias(): JsonResponse
    {
        $cargos = Cargo::soloCargos()
            ->activos()
            ->orderBy('nombre')
            ->get()
            ->map(function ($cargo) {
                return [
                    'id' => $cargo->id,
                    'nombre' => $cargo->nombre,
                    'ruta_jerarquica' => $cargo->getRutaJerarquica(),
                    'descripcion' => $cargo->descripcion,
                ];
            });

        return response()->json($cargos);
    }
}
