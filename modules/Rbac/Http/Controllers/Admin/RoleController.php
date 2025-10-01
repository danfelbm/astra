<?php

namespace Modules\Rbac\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\AdminController;
use Modules\Core\Models\Role;
use Modules\Core\Models\Segment;
use Modules\Core\Services\TenantService;
use Modules\Core\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends AdminController
{
    use HasAdvancedFilters;

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
        // Verificación de permisos usando Spatie
        abort_unless(auth()->user()->can('roles.view'), 403, 'No tienes permisos para ver roles');
        
        // Obtener el tenant actual (si el servicio está disponible)
        $currentTenantId = null;
        try {
            $currentTenantId = app(\Modules\Core\Services\TenantService::class)->getCurrentTenant()?->id;
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
        if (!$user || !$user->hasRole('super_admin')) {
            $query->where('name', '!=', 'super_admin');
        }

        // Paginación
        $roles = $query->orderBy('id', 'asc')
                      ->paginate(10)
                      ->withQueryString();

        // Obtener todos los segmentos disponibles (solo id y nombre para evitar memoria)
        $segments = Segment::select('id', 'name', 'description')->get();

        return Inertia::render('Modules/Rbac/Admin/Roles/Index', [
            'roles' => $roles,
            'segments' => $segments,
            'filters' => $request->only(['search']),
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
            'availablePermissions' => $this->getAvailablePermissions(),
            'canCreate' => auth()->user()->can('roles.create'),
            'canEdit' => auth()->user()->can('roles.edit'),
            'canDelete' => auth()->user()->can('roles.delete'),
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
    {
        // Verificación de permisos usando Spatie
        abort_unless(auth()->user()->can('roles.create'), 403, 'No tienes permisos para crear roles');

        return Inertia::render('Modules/Rbac/Admin/Roles/Create', [
            'segments' => Segment::select('id', 'name', 'description')->get(),
            'availablePermissions' => $this->getAvailablePermissions(),
        ]);
    }

    /**
     * Crear nuevo rol
     */
    public function store(Request $request)
    {
        // Verificación de permisos usando Spatie
        abort_unless(auth()->user()->can('roles.create'), 403, 'No tienes permisos para crear roles');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_administrative' => 'nullable|boolean',
            'redirect_after_login' => 'nullable|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name', // Validar que los permisos existan
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
        
        // Convertir 'default' a null para la base de datos
        $redirectAfterLogin = ($validated['redirect_after_login'] ?? null) === 'default' 
            ? null 
            : ($validated['redirect_after_login'] ?? null);
        
        $role = Role::create([
            'tenant_id' => $currentTenant ? $currentTenant->id : null,
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'is_administrative' => $validated['is_administrative'] ?? false,
            'redirect_after_login' => $redirectAfterLogin,
            // Eliminado campo legacy_permissions - usando solo Spatie
        ]);

        // Sincronizar permisos con Spatie
        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

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
        // Verificación de permisos usando Spatie
        abort_unless(auth()->user()->can('roles.view'), 403, 'No tienes permisos para ver roles');
        
        // Cargar solo datos esenciales para evitar referencias circulares
        $role->loadCount(['users', 'segments']);
        $role->load(['segments:id,name,description']);
        
        // Cargar permisos del rol usando Spatie
        $role->permissions = $role->permissions()->pluck('name')->toArray();

        return Inertia::render('Modules/Rbac/Admin/Roles/Show', [
            'role' => $role,
            'userCount' => $role->users_count,
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Role $role): Response
    {
        // Verificación de permisos usando Spatie
        abort_unless(auth()->user()->can('roles.edit'), 403, 'No tienes permisos para editar roles');

        // No permitir editar roles del sistema si no es super admin
        if ($role->isSystemRole() && !auth()->user()->hasRole('super_admin')) {
            abort(403, 'No se pueden editar roles del sistema');
        }

        // Cargar solo los IDs de segments para evitar problemas de memoria
        $role->load('segments:id,name');
        
        // Cargar permisos del rol usando Spatie y obtener solo los nombres
        $rolePermissions = $role->permissions()->pluck('name')->toArray();
        
        // Agregar los permisos al objeto role para que el frontend los pueda usar
        $role->permissions = $rolePermissions;

        return Inertia::render('Modules/Rbac/Admin/Roles/Edit', [
            'role' => $role,
            'segments' => Segment::select('id', 'name', 'description')->get(),
            'availablePermissions' => $this->getAvailablePermissions(),
            'selectedSegments' => $role->segments->pluck('id')->toArray(),
        ]);
    }

    /**
     * Actualizar rol
     */
    public function update(Request $request, Role $role)
    {
        // Verificación de permisos usando Spatie
        abort_unless(auth()->user()->can('roles.edit'), 403, 'No tienes permisos para editar roles');

        // No permitir editar roles del sistema si no es super admin
        if ($role->isSystemRole() && !auth()->user()->hasRole('super_admin')) {
            return back()->with('error', 'No se pueden editar roles del sistema');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_administrative' => 'nullable|boolean',
            'redirect_after_login' => 'nullable|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name', // Validar que los permisos existan
            'segment_ids' => 'array',
            'segment_ids.*' => 'exists:segments,id',
        ]);

        // Convertir 'default' a null para la base de datos
        $redirectAfterLogin = ($validated['redirect_after_login'] ?? null) === 'default' 
            ? null 
            : ($validated['redirect_after_login'] ?? null);
        
        $role->update([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'is_administrative' => $validated['is_administrative'] ?? false,
            'redirect_after_login' => $redirectAfterLogin,
            // Eliminado campo legacy_permissions - usando solo Spatie
        ]);

        // Sincronizar permisos con Spatie
        $role->syncPermissions($validated['permissions'] ?? []);

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
        // Verificación de permisos usando Spatie
        abort_unless(auth()->user()->can('roles.delete'), 403, 'No tienes permisos para eliminar roles');

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
        // Obtener permisos del rol usando Spatie
        $permissions = $role->permissions()->pluck('name')->toArray();
        
        return response()->json([
            'permissions' => $permissions,
        ]);
    }

    /**
     * Asociar segmentos a un rol
     */
    public function attachSegments(Request $request, Role $role)
    {
        // Verificación de permisos usando Spatie
        abort_unless(auth()->user()->can('roles.edit'), 403, 'No tienes permisos para asociar segmentos a roles');

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
                        'users.import' => 'Importar usuarios desde CSV',
                        'users.assign_roles' => 'Asignar roles a usuarios',
                        'users.update_requests' => 'Ver solicitudes de actualización',
                        'users.approve_updates' => 'Aprobar/rechazar actualizaciones',
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
                        'candidaturas.configuracion' => 'Acceder a configuración de candidaturas',
                        'candidaturas.notificaciones' => 'Enviar notificaciones de estado pendiente',
                        'candidaturas.recordatorios' => 'Enviar recordatorios a borradores',
                        'candidaturas.comment' => 'Comentar candidaturas',
                        'candidaturas.aprobar_campos' => 'Aprobar campos individuales',
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
                'campanas_plantillas' => [
                    'label' => 'Plantillas de Campañas',
                    'permissions' => [
                        'campanas.plantillas.view' => 'Ver plantillas',
                        'campanas.plantillas.create' => 'Crear plantillas',
                        'campanas.plantillas.edit' => 'Editar plantillas',
                        'campanas.plantillas.delete' => 'Eliminar plantillas',
                    ],
                ],
                'campanas' => [
                    'label' => 'Campañas (Admin)',
                    'permissions' => [
                        'campanas.view' => 'Ver campañas',
                        'campanas.create' => 'Crear campañas',
                        'campanas.edit' => 'Editar campañas',
                        'campanas.delete' => 'Eliminar campañas',
                        'campanas.send' => 'Enviar campañas',
                        'campanas.pause' => 'Pausar campañas',
                        'campanas.resume' => 'Reanudar campañas',
                        'campanas.cancel' => 'Cancelar campañas',
                        'campanas.export' => 'Exportar reportes',
                    ],
                ],
                'proyectos' => [
                    'label' => 'Proyectos (Admin)',
                    'permissions' => [
                        'proyectos.view' => 'Ver proyectos',
                        'proyectos.create' => 'Crear proyectos',
                        'proyectos.edit' => 'Editar proyectos',
                        'proyectos.delete' => 'Eliminar proyectos',
                        'proyectos.manage_fields' => 'Gestionar campos personalizados',
                        'proyectos.manage_tags' => 'Gestionar etiquetas',
                        'proyectos.manage_contracts' => 'Gestionar contratos',
                        'proyectos.manage_milestones' => 'Gestionar hitos',
                        'proyectos.manage_users' => 'Gestionar usuarios',
                        'proyectos.view_assigned' => 'Ver proyectos asignados',
                        'proyectos.view_contracts' => 'Ver contratos',
                        'proyectos.view_milestones' => 'Ver hitos',
                        'proyectos.export' => 'Exportar proyectos',
                        'proyectos.import' => 'Importar proyectos',
                        'proyectos.view_reports' => 'Ver reportes',
                    ],
                ],
                'contratos' => [
                    'label' => 'Contratos (Admin)',
                    'permissions' => [
                        'contratos.view' => 'Ver contratos',
                        'contratos.create' => 'Crear contratos',
                        'contratos.edit' => 'Editar contratos',
                        'contratos.delete' => 'Eliminar contratos',
                        'contratos.approve' => 'Aprobar contratos',
                        'contratos.reject' => 'Rechazar contratos',
                        'contratos.change_status' => 'Cambiar estado',
                        'contratos.manage_fields' => 'Gestionar campos',
                        'contratos.export' => 'Exportar contratos',
                        'contratos.import' => 'Importar contratos',
                        'contratos.download' => 'Descargar archivos',
                    ],
                ],
                'etiquetas' => [
                    'label' => 'Etiquetas',
                    'permissions' => [
                        'etiquetas.view' => 'Ver etiquetas',
                        'etiquetas.create' => 'Crear etiquetas',
                        'etiquetas.edit' => 'Editar etiquetas',
                        'etiquetas.delete' => 'Eliminar etiquetas',
                    ],
                ],
                'categorias_etiquetas' => [
                    'label' => 'Categorías de Etiquetas',
                    'permissions' => [
                        'categorias_etiquetas.view' => 'Ver categorías',
                        'categorias_etiquetas.create' => 'Crear categorías',
                        'categorias_etiquetas.edit' => 'Editar categorías',
                        'categorias_etiquetas.delete' => 'Eliminar categorías',
                    ],
                ],
                'campos_personalizados' => [
                    'label' => 'Campos Personalizados',
                    'permissions' => [
                        'campos_personalizados.view' => 'Ver campos personalizados',
                        'campos_personalizados.create' => 'Crear campos personalizados',
                        'campos_personalizados.edit' => 'Editar campos personalizados',
                        'campos_personalizados.delete' => 'Eliminar campos personalizados',
                    ],
                ],
                'hitos' => [
                    'label' => 'Hitos',
                    'permissions' => [
                        'hitos.view' => 'Ver hitos',
                        'hitos.create' => 'Crear hitos',
                        'hitos.edit' => 'Editar hitos',
                        'hitos.delete' => 'Eliminar hitos',
                        'hitos.complete' => 'Completar hitos',
                        'hitos.update_progress' => 'Actualizar progreso',
                        'hitos.manage_deliverables' => 'Gestionar entregables',
                    ],
                ],
                'entregables' => [
                    'label' => 'Entregables',
                    'permissions' => [
                        'entregables.view' => 'Ver entregables',
                        'entregables.create' => 'Crear entregables',
                        'entregables.edit' => 'Editar entregables',
                        'entregables.delete' => 'Eliminar entregables',
                        'entregables.assign' => 'Asignar entregables',
                        'entregables.complete' => 'Completar entregables',
                    ],
                ],
                'obligaciones' => [
                    'label' => 'Obligaciones de Contratos',
                    'permissions' => [
                        'obligaciones.view' => 'Ver obligaciones',
                        'obligaciones.create' => 'Crear obligaciones',
                        'obligaciones.edit' => 'Editar obligaciones',
                        'obligaciones.delete' => 'Eliminar obligaciones',
                        'obligaciones.complete' => 'Completar obligaciones',
                        'obligaciones.export' => 'Exportar obligaciones',
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
                        'votaciones.view_own_vote' => 'Ver mi voto emitido',
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
                'proyectos_usuarios' => [
                    'label' => 'Mis Proyectos',
                    'permissions' => [
                        'proyectos.view_own' => 'Ver proyectos propios',
                        'proyectos.create_own' => 'Crear proyectos propios',
                        'proyectos.edit_own' => 'Editar proyectos propios',
                        'proyectos.delete_own' => 'Eliminar proyectos propios',
                    ],
                ],
                'contratos_usuarios' => [
                    'label' => 'Mis Contratos',
                    'permissions' => [
                        'contratos.view_own' => 'Ver contratos propios',
                        'contratos.edit_own' => 'Editar contratos propios',
                        'contratos.view_public' => 'Ver contratos públicos',
                    ],
                ],
                'hitos_usuarios' => [
                    'label' => 'Mis Hitos',
                    'permissions' => [
                        'hitos.view_own' => 'Ver hitos propios',
                        'hitos.complete_own' => 'Completar hitos propios',
                    ],
                ],
                'obligaciones_usuarios' => [
                    'label' => 'Mis Obligaciones',
                    'permissions' => [
                        'obligaciones.view_own' => 'Ver obligaciones propias',
                        'obligaciones.complete_own' => 'Completar obligaciones propias',
                    ],
                ],
            ],
        ];
    }
}
