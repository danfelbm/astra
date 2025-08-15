<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Segment;
use App\Services\TenantService;
use App\Traits\HasAdvancedFilters;
use App\Traits\AuthorizesActions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    use HasAdvancedFilters, AuthorizesActions;

    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Mostrar lista de roles
     */
    public function index(Request $request): Response
    {
        // Verificación de permisos adicional como respaldo
        $this->authorizeAction('roles.view');
        
        // Obtener el tenant actual (si el servicio está disponible)
        $currentTenantId = null;
        try {
            $currentTenantId = app(\App\Services\TenantService::class)->getCurrentTenant()?->id;
        } catch (\Exception $e) {
            // Si no hay TenantService o está deshabilitado, continuar sin tenant
        }
        
        // Mostrar roles del sistema y roles del tenant actual
        // IMPORTANTE: No cargar relaciones completas para evitar referencias circulares
        // que causan memory exhausted al serializar para Inertia
        $query = Role::query()
            ->withCount(['users', 'segments'])
            ->with(['segments:id,name']); // Solo cargar id y nombre de segments
        
        // Si hay tenant, filtrar por él; si no, mostrar todos
        if ($currentTenantId !== null) {
            $query->where(function($q) use ($currentTenantId) {
                $q->whereNull('tenant_id')  // Roles del sistema
                  ->orWhere('tenant_id', $currentTenantId); // Roles del tenant actual
            });
        }

        // Aplicar filtros simples
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('display_name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Aplicar filtros avanzados
        $query = $this->applyAdvancedFilters($query, $request);

        // Si no es super admin, no mostrar el rol super_admin
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            $query->where('name', '!=', 'super_admin');
        }

        // Paginación
        $roles = $query->orderBy('id', 'asc')
                      ->paginate(10)
                      ->withQueryString();

        // Obtener todos los segmentos disponibles (solo id y nombre para evitar memoria)
        $segments = Segment::select('id', 'name', 'description')->get();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'segments' => $segments,
            'filters' => $request->only(['search']),
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
            'availablePermissions' => $this->getAvailablePermissions(),
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
    {
        // Verificación de permisos específicos en lugar de isAdmin()
        $this->authorizeAction('roles.create');

        return Inertia::render('Admin/Roles/Create', [
            'segments' => Segment::select('id', 'name', 'description')->get(),
            'availablePermissions' => $this->getAvailablePermissions(),
            'modules' => $this->getAvailableModules(),
        ]);
    }

    /**
     * Crear nuevo rol
     */
    public function store(Request $request)
    {
        // Verificación de permisos específicos en lugar de isAdmin()
        $this->authorizeAction('roles.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_administrative' => 'nullable|boolean',
            'permissions' => 'array',
            'permissions.*' => 'string',
            'allowed_modules' => 'array',
            'allowed_modules.*' => 'string',
            'segment_ids' => 'array',
            'segment_ids.*' => 'exists:segments,id',
        ]);

        // Obtener el tenant actual (si está disponible)
        $currentTenant = null;
        try {
            $currentTenant = $this->tenantService->getCurrentTenant();
        } catch (\Exception $e) {
            // Si no hay TenantService o está deshabilitado, continuar sin tenant
        }
        
        $role = Role::create([
            'tenant_id' => $currentTenant ? $currentTenant->id : null,
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'is_administrative' => $validated['is_administrative'] ?? false,
            'permissions' => $validated['permissions'] ?? [],
            'allowed_modules' => $validated['allowed_modules'] ?? [],
        ]);

        // Asociar segmentos si se proporcionaron
        if (!empty($validated['segment_ids'])) {
            $role->segments()->attach($validated['segment_ids']);
        }

        return redirect()->route('admin.roles.index')
                        ->with('success', 'Rol creado exitosamente');
    }

    /**
     * Mostrar detalles de un rol
     */
    public function show(Role $role): Response
    {
        // Verificación de permisos adicional como respaldo
        $this->authorizeAction('roles.view');
        
        // Cargar solo datos esenciales para evitar referencias circulares
        $role->loadCount(['users', 'segments']);
        $role->load(['segments:id,name,description']);

        return Inertia::render('Admin/Roles/Show', [
            'role' => $role,
            'userCount' => $role->users_count,
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Role $role): Response
    {
        // Verificación de permisos específicos en lugar de isAdmin()
        $this->authorizeAction('roles.edit');

        // No permitir editar roles del sistema si no es super admin
        if ($role->isSystemRole() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'No se pueden editar roles del sistema');
        }

        // Cargar solo los IDs de segments para evitar problemas de memoria
        $role->load('segments:id,name');

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $role,
            'segments' => Segment::select('id', 'name', 'description')->get(),
            'availablePermissions' => $this->getAvailablePermissions(),
            'modules' => $this->getAvailableModules(),
            'selectedSegments' => $role->segments->pluck('id')->toArray(),
        ]);
    }

    /**
     * Actualizar rol
     */
    public function update(Request $request, Role $role)
    {
        // Verificación de permisos específicos en lugar de isAdmin()
        $this->authorizeAction('roles.edit');

        // No permitir editar roles del sistema si no es super admin
        if ($role->isSystemRole() && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'No se pueden editar roles del sistema');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_administrative' => 'nullable|boolean',
            'permissions' => 'array',
            'permissions.*' => 'string',
            'allowed_modules' => 'array',
            'allowed_modules.*' => 'string',
            'segment_ids' => 'array',
            'segment_ids.*' => 'exists:segments,id',
        ]);

        $role->update([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'is_administrative' => $validated['is_administrative'] ?? false,
            'permissions' => $validated['permissions'] ?? [],
            'allowed_modules' => $validated['allowed_modules'] ?? [],
        ]);

        // Sincronizar segmentos
        $role->segments()->sync($validated['segment_ids'] ?? []);

        return redirect()->route('admin.roles.index')
                        ->with('success', 'Rol actualizado exitosamente');
    }

    /**
     * Eliminar rol
     */
    public function destroy(Role $role)
    {
        // Verificación de permisos específicos en lugar de isAdmin()
        $this->authorizeAction('roles.delete');

        // No permitir eliminar roles del sistema
        if ($role->isSystemRole()) {
            return back()->with('error', 'No se pueden eliminar roles del sistema');
        }

        // Verificar si tiene usuarios asignados
        if ($role->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un rol con usuarios asignados');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
                        ->with('success', 'Rol eliminado exitosamente');
    }

    /**
     * Obtener permisos de un rol
     */
    public function permissions(Role $role)
    {
        return response()->json([
            'permissions' => $role->permissions ?? [],
            'allowed_modules' => $role->allowed_modules ?? [],
        ]);
    }

    /**
     * Asociar segmentos a un rol
     */
    public function attachSegments(Request $request, Role $role)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'segment_ids' => 'array',
            'segment_ids.*' => 'exists:segments,id',
        ]);

        $role->segments()->sync($validated['segment_ids'] ?? []);

        return back()->with('success', 'Segmentos actualizados exitosamente');
    }

    /**
     * Configuración de campos para filtros avanzados
     */
    protected function getFilterFieldsConfig(): array
    {
        return [
            [
                'field' => 'name',
                'label' => 'Nombre del Rol',
                'type' => 'text',
            ],
            [
                'field' => 'display_name',
                'label' => 'Nombre a Mostrar',
                'type' => 'text',
            ],
            [
                'field' => 'description',
                'label' => 'Descripción',
                'type' => 'text',
            ],
            [
                'field' => 'created_at',
                'label' => 'Fecha de Creación',
                'type' => 'date',
            ],
        ];
    }

    /**
     * Obtener lista de permisos disponibles
     * Ahora retorna permisos separados para roles administrativos y frontend
     */
    private function getAvailablePermissions(): array
    {
        return [
            'administrative' => [
                'users' => [
                    'label' => 'Usuarios',
                    'permissions' => [
                        'users.view' => 'Ver usuarios',
                        'users.create' => 'Crear usuarios',
                        'users.edit' => 'Editar usuarios',
                        'users.delete' => 'Eliminar usuarios',
                        'users.export' => 'Exportar usuarios',
                        'users.assign_roles' => 'Asignar roles a usuarios',
                    ],
                ],
                'votaciones' => [
                    'label' => 'Votaciones (Admin)',
                    'permissions' => [
                        'votaciones.view' => 'Ver todas las votaciones',
                        'votaciones.create' => 'Crear votaciones',
                        'votaciones.edit' => 'Editar votaciones',
                        'votaciones.delete' => 'Eliminar votaciones',
                        'votaciones.manage_voters' => 'Gestionar votantes',
                    ],
                ],
                'asambleas' => [
                    'label' => 'Asambleas (Admin)',
                    'permissions' => [
                        'asambleas.view' => 'Ver todas las asambleas',
                        'asambleas.create' => 'Crear asambleas',
                        'asambleas.edit' => 'Editar asambleas',
                        'asambleas.delete' => 'Eliminar asambleas',
                        'asambleas.manage_participants' => 'Gestionar participantes',
                    ],
                ],
                'convocatorias' => [
                    'label' => 'Convocatorias (Admin)',
                    'permissions' => [
                        'convocatorias.view' => 'Ver todas las convocatorias',
                        'convocatorias.create' => 'Crear convocatorias',
                        'convocatorias.edit' => 'Editar convocatorias',
                        'convocatorias.delete' => 'Eliminar convocatorias',
                    ],
                ],
                'postulaciones' => [
                    'label' => 'Postulaciones (Admin)',
                    'permissions' => [
                        'postulaciones.view' => 'Ver todas las postulaciones',
                        'postulaciones.review' => 'Revisar postulaciones',
                        'postulaciones.approve' => 'Aprobar postulaciones',
                        'postulaciones.reject' => 'Rechazar postulaciones',
                    ],
                ],
                'candidaturas' => [
                    'label' => 'Candidaturas (Admin)',
                    'permissions' => [
                        'candidaturas.view' => 'Ver todas las candidaturas',
                        'candidaturas.create' => 'Crear candidaturas para otros',
                        'candidaturas.approve' => 'Aprobar candidaturas',
                        'candidaturas.reject' => 'Rechazar candidaturas',
                    ],
                ],
                'cargos' => [
                    'label' => 'Cargos',
                    'permissions' => [
                        'cargos.view' => 'Ver cargos',
                        'cargos.create' => 'Crear cargos',
                        'cargos.edit' => 'Editar cargos',
                        'cargos.delete' => 'Eliminar cargos',
                    ],
                ],
                'periodos' => [
                    'label' => 'Periodos Electorales',
                    'permissions' => [
                        'periodos.view' => 'Ver periodos electorales',
                        'periodos.create' => 'Crear periodos electorales',
                        'periodos.edit' => 'Editar periodos electorales',
                        'periodos.delete' => 'Eliminar periodos electorales',
                    ],
                ],
                'reports' => [
                    'label' => 'Reportes',
                    'permissions' => [
                        'reports.view' => 'Ver reportes',
                        'reports.export' => 'Exportar reportes',
                        'reports.generate' => 'Generar reportes',
                    ],
                ],
                'roles' => [
                    'label' => 'Roles',
                    'permissions' => [
                        'roles.view' => 'Ver roles',
                        'roles.create' => 'Crear roles',
                        'roles.edit' => 'Editar roles',
                        'roles.delete' => 'Eliminar roles',
                    ],
                ],
                'segments' => [
                    'label' => 'Segmentos',
                    'permissions' => [
                        'segments.view' => 'Ver segmentos',
                        'segments.create' => 'Crear segmentos',
                        'segments.edit' => 'Editar segmentos',
                        'segments.delete' => 'Eliminar segmentos',
                    ],
                ],
                'settings' => [
                    'label' => 'Configuración',
                    'permissions' => [
                        'settings.view' => 'Ver configuración',
                        'settings.edit' => 'Editar configuración',
                    ],
                ],
                'dashboard' => [
                    'label' => 'Dashboard Admin',
                    'permissions' => [
                        'dashboard.admin' => 'Ver dashboard administrativo',
                    ],
                ],
                'formularios' => [
                    'label' => 'Formularios (Admin)',
                    'permissions' => [
                        'formularios.view' => 'Ver todos los formularios',
                        'formularios.create' => 'Crear formularios',
                        'formularios.edit' => 'Editar formularios',
                        'formularios.delete' => 'Eliminar formularios',
                        'formularios.view_responses' => 'Ver respuestas de formularios',
                        'formularios.export' => 'Exportar respuestas',
                        'formularios.manage_permissions' => 'Gestionar permisos de formularios',
                    ],
                ],
            ],
            'frontend' => [
                'votaciones' => [
                    'label' => 'Votaciones',
                    'permissions' => [
                        'votaciones.view_public' => 'Ver votaciones disponibles',
                        'votaciones.vote' => 'Participar en votaciones',
                        'votaciones.view_results' => 'Ver resultados públicos',
                    ],
                ],
                'asambleas' => [
                    'label' => 'Asambleas',
                    'permissions' => [
                        'asambleas.view_public' => 'Ver asambleas públicas',
                        'asambleas.participate' => 'Participar en asambleas',
                        'asambleas.view_minutes' => 'Ver actas de asambleas',
                    ],
                ],
                'convocatorias' => [
                    'label' => 'Convocatorias',
                    'permissions' => [
                        'convocatorias.view_public' => 'Ver convocatorias públicas',
                        'convocatorias.apply' => 'Aplicar a convocatorias',
                    ],
                ],
                'postulaciones' => [
                    'label' => 'Postulaciones',
                    'permissions' => [
                        'postulaciones.create' => 'Crear postulaciones propias',
                        'postulaciones.view_own' => 'Ver postulaciones propias',
                        'postulaciones.edit_own' => 'Editar postulaciones propias',
                        'postulaciones.delete_own' => 'Eliminar postulaciones propias',
                    ],
                ],
                'candidaturas' => [
                    'label' => 'Mi Candidatura',
                    'permissions' => [
                        'candidaturas.create_own' => 'Crear candidatura propia',
                        'candidaturas.view_own' => 'Ver candidatura propia',
                        'candidaturas.edit_own' => 'Editar candidatura propia',
                        'candidaturas.view_public' => 'Ver candidaturas públicas',
                    ],
                ],
                'profile' => [
                    'label' => 'Mi Perfil',
                    'permissions' => [
                        'profile.view' => 'Ver perfil propio',
                        'profile.edit' => 'Editar perfil propio',
                        'profile.change_password' => 'Cambiar contraseña',
                    ],
                ],
                'dashboard' => [
                    'label' => 'Dashboard',
                    'permissions' => [
                        'dashboard.view' => 'Ver dashboard personal',
                    ],
                ],
                'formularios' => [
                    'label' => 'Formularios',
                    'permissions' => [
                        'formularios.view_public' => 'Ver formularios públicos',
                        'formularios.fill_public' => 'Llenar formularios públicos',
                    ],
                ],
            ],
        ];
    }

    /**
     * Obtener lista de módulos disponibles
     * Ahora retorna módulos separados para roles administrativos y frontend
     */
    private function getAvailableModules(): array
    {
        return [
            'administrative' => [
                'dashboard' => 'Dashboard Admin',
                'users' => 'Usuarios',
                'roles' => 'Roles y Permisos',
                'segments' => 'Segmentos',
                'votaciones' => 'Votaciones (Gestión)',
                'asambleas' => 'Asambleas (Gestión)',
                'convocatorias' => 'Convocatorias (Gestión)',
                'postulaciones' => 'Postulaciones (Gestión)',
                'candidaturas' => 'Candidaturas (Gestión)',
                'formularios' => 'Formularios (Gestión)',
                'cargos' => 'Cargos',
                'periodos' => 'Periodos Electorales',
                'reports' => 'Reportes',
                'settings' => 'Configuración',
            ],
            'frontend' => [
                'dashboard' => 'Mi Dashboard',
                'votaciones' => 'Mis Votaciones',
                'asambleas' => 'Asambleas',
                'convocatorias' => 'Convocatorias',
                'postulaciones' => 'Mis Postulaciones',
                'candidaturas' => 'Mi Candidatura',
                'formularios' => 'Formularios',
                'profile' => 'Mi Perfil',
            ],
        ];
    }
}
