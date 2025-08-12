<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantService;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    use HasAdvancedFilters;

    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Mostrar lista de tenants (solo super admin)
     */
    public function index(Request $request): Response
    {
        // Verificar que el usuario sea super admin
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'No autorizado');
        }

        $query = Tenant::query();

        // Aplicar filtros simples
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('subdomain', 'like', "%{$request->search}%")
                  ->orWhere('subscription_plan', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', $request->active === 'true');
        }

        // Aplicar filtros avanzados
        $query = $this->applyAdvancedFilters($query, $request);

        // Paginación
        $tenants = $query->orderBy('created_at', 'desc')
                        ->paginate(10)
                        ->withQueryString();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'active']),
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
            'currentTenant' => $this->tenantService->getCurrentTenant(),
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'No autorizado');
        }

        return Inertia::render('Admin/Tenants/Create', [
            'subscriptionPlans' => [
                'basic' => 'Plan Básico',
                'professional' => 'Plan Profesional',
                'enterprise' => 'Plan Empresarial',
            ],
            'timezones' => $this->getAmericaTimezones(),
        ]);
    }

    /**
     * Crear nuevo tenant
     */
    public function store(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:255|unique:tenants,subdomain|regex:/^[a-z0-9-]+$/',
            'subscription_plan' => 'required|in:basic,professional,enterprise',
            'active' => 'boolean',
            'settings.logo' => 'nullable|string',
            'settings.primary_color' => 'nullable|string',
            'settings.otp_expiration' => 'nullable|integer|min:5|max:60',
            'settings.timezone' => 'nullable|string',
            'limits.max_users' => 'nullable|integer|min:0',
            'limits.max_votaciones' => 'nullable|integer|min:0',
            'limits.max_convocatorias' => 'nullable|integer|min:0',
        ]);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'subdomain' => $validated['subdomain'],
            'subscription_plan' => $validated['subscription_plan'],
            'active' => $validated['active'] ?? true,
            'settings' => $validated['settings'] ?? [
                'logo' => null,
                'primary_color' => '#3B82F6',
                'otp_expiration' => 10,
                'timezone' => 'America/Bogota',
            ],
            'limits' => $validated['limits'] ?? [
                'max_users' => null,
                'max_votaciones' => null,
                'max_convocatorias' => null,
            ],
        ]);

        // Copiar roles del sistema para el nuevo tenant
        $this->createSystemRolesForTenant($tenant);

        return redirect()->route('admin.tenants.index')
                        ->with('success', 'Tenant creado exitosamente con roles del sistema configurados');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Tenant $tenant): Response
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'No autorizado');
        }

        return Inertia::render('Admin/Tenants/Edit', [
            'tenant' => $tenant,
            'subscriptionPlans' => [
                'basic' => 'Plan Básico',
                'professional' => 'Plan Profesional',
                'enterprise' => 'Plan Empresarial',
            ],
            'timezones' => $this->getAmericaTimezones(),
            'userCount' => $tenant->users()->count(),
            'votacionCount' => $tenant->votaciones()->count(),
            'convocatoriaCount' => $tenant->convocatorias()->count(),
        ]);
    }

    /**
     * Actualizar tenant
     */
    public function update(Request $request, Tenant $tenant)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:255|regex:/^[a-z0-9-]+$/|unique:tenants,subdomain,' . $tenant->id,
            'subscription_plan' => 'required|in:basic,professional,enterprise',
            'active' => 'boolean',
            'settings.logo' => 'nullable|string',
            'settings.primary_color' => 'nullable|string',
            'settings.otp_expiration' => 'nullable|integer|min:5|max:60',
            'settings.timezone' => 'nullable|string',
            'limits.max_users' => 'nullable|integer|min:0',
            'limits.max_votaciones' => 'nullable|integer|min:0',
            'limits.max_convocatorias' => 'nullable|integer|min:0',
        ]);

        $tenant->update([
            'name' => $validated['name'],
            'subdomain' => $validated['subdomain'],
            'subscription_plan' => $validated['subscription_plan'],
            'active' => $validated['active'] ?? true,
            'settings' => array_merge($tenant->settings ?? [], $validated['settings'] ?? []),
            'limits' => array_merge($tenant->limits ?? [], $validated['limits'] ?? []),
        ]);

        return redirect()->route('admin.tenants.index')
                        ->with('success', 'Tenant actualizado exitosamente');
    }

    /**
     * Eliminar tenant
     */
    public function destroy(Tenant $tenant)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'No autorizado');
        }

        // No permitir eliminar el tenant principal
        if ($tenant->id === 1) {
            return back()->with('error', 'No se puede eliminar el tenant principal');
        }

        // Verificar si tiene datos
        if ($tenant->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un tenant con usuarios activos');
        }

        $tenant->delete();

        return redirect()->route('admin.tenants.index')
                        ->with('success', 'Tenant eliminado exitosamente');
    }

    /**
     * Cambiar tenant actual (super admin)
     */
    public function switch(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $this->tenantService->setCurrentTenant($tenant);

        return back()->with('success', 'Cambiado al tenant: ' . $tenant->name);
    }

    /**
     * Configuración de campos para filtros avanzados
     */
    protected function getFilterFieldsConfig(): array
    {
        return [
            [
                'field' => 'name',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            [
                'field' => 'subdomain',
                'label' => 'Subdominio',
                'type' => 'text',
            ],
            [
                'field' => 'subscription_plan',
                'label' => 'Plan de Suscripción',
                'type' => 'select',
                'options' => [
                    ['value' => 'basic', 'label' => 'Básico'],
                    ['value' => 'professional', 'label' => 'Profesional'],
                    ['value' => 'enterprise', 'label' => 'Empresarial'],
                ],
            ],
            [
                'field' => 'active',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => '1', 'label' => 'Activo'],
                    ['value' => '0', 'label' => 'Inactivo'],
                ],
            ],
            [
                'field' => 'created_at',
                'label' => 'Fecha de Creación',
                'type' => 'date',
            ],
        ];
    }

    /**
     * Obtener zonas horarias de América
     */
    private function getAmericaTimezones(): array
    {
        return [
            'America/New_York' => 'Nueva York (GMT-5)',
            'America/Chicago' => 'Chicago (GMT-6)',
            'America/Denver' => 'Denver (GMT-7)',
            'America/Los_Angeles' => 'Los Angeles (GMT-8)',
            'America/Mexico_City' => 'Ciudad de México (GMT-6)',
            'America/Bogota' => 'Bogotá (GMT-5)',
            'America/Lima' => 'Lima (GMT-5)',
            'America/Santiago' => 'Santiago (GMT-3)',
            'America/Buenos_Aires' => 'Buenos Aires (GMT-3)',
            'America/Sao_Paulo' => 'São Paulo (GMT-3)',
            'America/Caracas' => 'Caracas (GMT-4)',
        ];
    }
    
    /**
     * Crear roles del sistema para un nuevo tenant
     */
    private function createSystemRolesForTenant(Tenant $tenant): void
    {
        // Obtener roles del sistema (excluyendo super_admin)
        $systemRoles = \App\Models\Role::systemRoles()
            ->where('name', '!=', 'super_admin')
            ->get();
        
        foreach ($systemRoles as $systemRole) {
            // Copiar el rol para el tenant
            $systemRole->copyForTenant($tenant->id);
        }
    }
}
