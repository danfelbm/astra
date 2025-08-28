<?php

namespace App\Http\Controllers\Rbac\Admin;

use App\Http\Controllers\Core\AdminController;
use App\Models\Core\Segment;
use App\Models\Core\Role;
use App\Models\Core\User;
use App\Models\Geografico\Departamento;
use App\Models\Geografico\Localidad;
use App\Models\Geografico\Municipio;
use App\Models\Geografico\Territorio;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SegmentController extends AdminController
{
    use HasAdvancedFilters;

    /**
     * Mostrar lista de segmentos
     */
    public function index(Request $request): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('segments.view'), 403, 'No tienes permisos para ver segmentos');
        
        $query = Segment::query()->with(['roles', 'createdBy']);

        // Aplicar filtros simples
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%")
                  ->orWhere('model_type', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('is_dynamic')) {
            $query->where('is_dynamic', $request->is_dynamic === 'true');
        }

        // Aplicar filtros avanzados
        $query = $this->applyAdvancedFilters($query, $request);

        // Paginación
        $segments = $query->orderBy('created_at', 'desc')
                         ->paginate(10)
                         ->withQueryString();

        // Agregar conteo de usuarios a cada segmento
        $segments->each(function ($segment) {
            $segment->user_count = $segment->getCount();
        });

        return Inertia::render('Admin/Segments/Index', [
            'segments' => $segments,
            'filters' => $request->only(['search', 'is_dynamic']),
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
            'canCreate' => auth()->user()->can('segments.create'),
            'canEdit' => auth()->user()->can('segments.edit'),
            'canDelete' => auth()->user()->can('segments.delete'),
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
    {
        // Verificación de permisos usando Spatie
        abort_unless(auth()->user()->can('segments.create'), 403, 'No tienes permisos para crear segmentos');

        return Inertia::render('Admin/Segments/Create', [
            'roles' => Role::all(),
            'filterFieldsConfig' => $this->getUserFilterFieldsConfig(),
            'modelTypes' => $this->getAvailableModelTypes(),
        ]);
    }

    /**
     * Crear nuevo segmento
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('segments.create'), 403, 'No tienes permisos para crear segmentos');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'model_type' => 'required|string',
            'filters' => 'required|array',
            'is_dynamic' => 'boolean',
            'cache_duration' => 'nullable|integer|min:0|max:86400',
            'role_ids' => 'array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        // Procesar los filtros para evitar doble serialización
        $filters = $validated['filters'];
        if (isset($filters['advanced_filters']) && is_string($filters['advanced_filters'])) {
            // Si advanced_filters viene como string JSON, decodificarlo
            $filters['advanced_filters'] = json_decode($filters['advanced_filters'], true);
        }

        $segment = Segment::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'model_type' => $validated['model_type'],
            'filters' => $filters,
            'is_dynamic' => $validated['is_dynamic'] ?? true,
            'cache_duration' => $validated['cache_duration'] ?? 300,
            'created_by' => auth()->id(),
            'metadata' => [
                'contacts_count' => 0,
                'last_calculated_at' => null,
            ],
        ]);

        // Asociar roles si se proporcionaron
        if (!empty($validated['role_ids'])) {
            $segment->roles()->attach($validated['role_ids']);
        }

        // Evaluar el segmento para obtener el conteo inicial
        $segment->evaluate();

        return redirect()->route('admin.segments.index')
                        ->with('success', 'Segmento creado exitosamente');
    }

    /**
     * Mostrar detalles de un segmento con usuarios
     */
    public function show(Segment $segment): Response
    {
        // Verificar permisos
        abort_unless(auth()->user()->can('segments.view'), 403, 'No tienes permisos para ver segmentos');
        
        $segment->load(['roles', 'createdBy']);

        // Obtener usuarios del segmento
        $users = $segment->evaluate();
        
        // Si son muchos usuarios, paginar
        $usersPaginated = null;
        if ($users->count() > 20) {
            $page = request()->get('page', 1);
            $perPage = 20;
            $usersPaginated = [
                'data' => $users->forPage($page, $perPage)->values(),
                'total' => $users->count(),
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($users->count() / $perPage),
            ];
        }

        return Inertia::render('Admin/Segments/Show', [
            'segment' => $segment,
            'users' => $usersPaginated ?? $users,
            'metadata' => $segment->metadata,
            'isPaginated' => $usersPaginated !== null,
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Segment $segment): Response
    {
        abort_unless(auth()->user()->can('segments.edit'), 403, 'No tienes permisos para editar segmentos');

        $segment->load('roles');

        return Inertia::render('Admin/Segments/Edit', [
            'segment' => $segment,
            'roles' => Role::all(),
            'filterFieldsConfig' => $this->getUserFilterFieldsConfig(),
            'modelTypes' => $this->getAvailableModelTypes(),
            'selectedRoles' => $segment->roles->pluck('id')->toArray(),
        ]);
    }

    /**
     * Actualizar segmento
     */
    public function update(Request $request, Segment $segment)
    {
        abort_unless(auth()->user()->can('segments.edit'), 403, 'No tienes permisos para editar segmentos');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'model_type' => 'required|string',
            'filters' => 'required|array',
            'is_dynamic' => 'boolean',
            'cache_duration' => 'nullable|integer|min:0|max:86400',
            'role_ids' => 'array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        // Procesar los filtros para evitar doble serialización
        $filters = $validated['filters'];
        if (isset($filters['advanced_filters']) && is_string($filters['advanced_filters'])) {
            // Si advanced_filters viene como string JSON, decodificarlo
            $filters['advanced_filters'] = json_decode($filters['advanced_filters'], true);
        }

        $segment->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'model_type' => $validated['model_type'],
            'filters' => $filters,
            'is_dynamic' => $validated['is_dynamic'] ?? true,
            'cache_duration' => $validated['cache_duration'] ?? 300,
        ]);

        // Sincronizar roles
        $segment->roles()->sync($validated['role_ids'] ?? []);

        // Limpiar cache y recalcular
        $segment->clearCache();
        $segment->evaluate();

        return redirect()->route('admin.segments.index')
                        ->with('success', 'Segmento actualizado exitosamente');
    }

    /**
     * Eliminar segmento
     */
    public function destroy(Segment $segment)
    {
        abort_unless(auth()->user()->can('segments.delete'), 403, 'No tienes permisos para eliminar segmentos');

        // Verificar si tiene roles asociados
        if ($segment->roles()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un segmento con roles asociados');
        }

        $segment->delete();

        return redirect()->route('admin.segments.index')
                        ->with('success', 'Segmento eliminado exitosamente');
    }

    /**
     * Evaluar/recalcular un segmento
     */
    public function evaluate(Segment $segment)
    {
        abort_unless(auth()->user()->can('segments.edit'), 403, 'No tienes permisos para evaluar segmentos');

        $results = $segment->recalculate();

        return response()->json([
            'success' => true,
            'user_count' => $results->count(),
            'metadata' => $segment->metadata,
        ]);
    }

    /**
     * Limpiar cache de un segmento
     */
    public function clearCache(Segment $segment)
    {
        abort_unless(auth()->user()->can('segments.edit'), 403, 'No tienes permisos para limpiar cache de segmentos');

        $segment->clearCache();

        return back()->with('success', 'Cache del segmento limpiado exitosamente');
    }

    /**
     * Configuración de campos para filtros avanzados de segmentos
     */
    protected function getFilterFieldsConfig(): array
    {
        return [
            [
                'name' => 'name',
                'label' => 'Nombre del Segmento',
                'type' => 'text',
            ],
            [
                'name' => 'description',
                'label' => 'Descripción',
                'type' => 'text',
            ],
            [
                'name' => 'model_type',
                'label' => 'Tipo de Modelo',
                'type' => 'select',
                'options' => [
                    ['value' => 'App\\Models\\Core\\User', 'label' => 'Usuarios'],
                ],
            ],
            [
                'name' => 'is_dynamic',
                'label' => 'Tipo',
                'type' => 'select',
                'options' => [
                    ['value' => '1', 'label' => 'Dinámico'],
                    ['value' => '0', 'label' => 'Estático'],
                ],
            ],
            [
                'name' => 'created_at',
                'label' => 'Fecha de Creación',
                'type' => 'date',
            ],
        ];
    }

    /**
     * Configuración de campos para filtros de usuarios
     */
    protected function getUserFilterFieldsConfig(): array
    {
        // Cargar opciones dinámicamente para los campos geográficos
        $territorios = Territorio::activos()
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get()
            ->map(fn($territorio) => [
                'value' => (string) $territorio->id,
                'label' => $territorio->nombre
            ])
            ->toArray();

        $departamentos = Departamento::activos()
            ->with('territorio:id,nombre')
            ->select('id', 'nombre', 'territorio_id')
            ->orderBy('nombre')
            ->get()
            ->map(fn($departamento) => [
                'value' => (string) $departamento->id,
                'label' => $departamento->territorio ? 
                    "{$departamento->territorio->nombre} - {$departamento->nombre}" : 
                    $departamento->nombre
            ])
            ->toArray();

        $municipios = Municipio::activos()
            ->with(['departamento:id,nombre', 'departamento.territorio:id,nombre'])
            ->select('id', 'nombre', 'departamento_id')
            ->orderBy('nombre')
            ->get()
            ->map(fn($municipio) => [
                'value' => (string) $municipio->id,
                'label' => $municipio->departamento && $municipio->departamento->territorio ? 
                    "{$municipio->departamento->territorio->nombre} - {$municipio->departamento->nombre} - {$municipio->nombre}" :
                    $municipio->nombre
            ])
            ->toArray();

        $localidades = Localidad::activos()
            ->with(['municipio:id,nombre', 'municipio.departamento:id,nombre', 'municipio.departamento.territorio:id,nombre'])
            ->select('id', 'nombre', 'municipio_id')
            ->orderBy('nombre')
            ->get()
            ->map(fn($localidad) => [
                'value' => (string) $localidad->id,
                'label' => $localidad->municipio && $localidad->municipio->departamento && $localidad->municipio->departamento->territorio ? 
                    "{$localidad->municipio->departamento->territorio->nombre} - {$localidad->municipio->departamento->nombre} - {$localidad->municipio->nombre} - {$localidad->nombre}" :
                    $localidad->nombre
            ])
            ->toArray();

        return [
            [
                'name' => 'name',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'text',
            ],
            [
                'name' => 'activo',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => '1', 'label' => 'Activo'],
                    ['value' => '0', 'label' => 'Inactivo'],
                ],
            ],
            [
                'name' => 'created_at',
                'label' => 'Fecha de Registro',
                'type' => 'date',
            ],
            [
                'name' => 'territorio_id',
                'label' => 'Territorio',
                'type' => 'select',
                'options' => $territorios,
            ],
            [
                'name' => 'departamento_id',
                'label' => 'Departamento',
                'type' => 'select',
                'options' => $departamentos,
            ],
            [
                'name' => 'municipio_id',
                'label' => 'Municipio',
                'type' => 'select',
                'options' => $municipios,
            ],
            [
                'name' => 'localidad_id',
                'label' => 'Localidad',
                'type' => 'select',
                'options' => $localidades,
            ],
        ];
    }

    /**
     * Obtener tipos de modelos disponibles para segmentación
     */
    private function getAvailableModelTypes(): array
    {
        return [
            'App\\Models\\Core\\User' => 'Usuarios',
            // En el futuro se pueden agregar más modelos
            // 'App\\Models\\Votante' => 'Votantes',
            // 'App\\Models\\Candidato' => 'Candidatos',
        ];
    }
}