<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use App\Services\ConfiguracionService;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        // Cargar roles del usuario si está autenticado
        $user = $request->user();
        if ($user) {
            $user->load('roles');
        }

        // Obtener información del tenant actual y disponibles para super admin
        $currentTenant = null;
        $availableTenants = null;
        if ($user && $user->isSuperAdmin()) {
            // Para super admin, obtener el tenant actual desde el servicio
            if (app()->bound(\App\Services\TenantService::class)) {
                $tenantService = app(\App\Services\TenantService::class);
                $currentTenant = $tenantService->getCurrentTenant();
                $availableTenants = \App\Models\Tenant::where('active', true)
                    ->select(['id', 'name', 'subdomain', 'active', 'subscription_plan'])
                    ->orderBy('name')
                    ->get();
            }
        } else if ($user) {
            // Para usuarios regulares, solo el tenant actual si existe
            if (app()->bound(\App\Services\TenantService::class)) {
                $tenantService = app(\App\Services\TenantService::class);
                $currentTenant = $tenantService->getCurrentTenant();
            }
        }

        // Obtener todos los permisos del usuario
        $permissions = [];
        $allowedModules = [];
        if ($user) {
            // Recolectar todos los permisos de todos los roles del usuario
            foreach ($user->roles as $role) {
                if ($role->permissions) {
                    $permissions = array_merge($permissions, $role->permissions);
                }
            }
            // Eliminar duplicados y reindexar
            $permissions = array_values(array_unique($permissions));
            
            // Obtener los módulos permitidos del usuario
            $allowedModules = $user->getAllowedModules();
        }

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'roles' => $user ? $user->roles : [],
                'permissions' => $permissions,
                'allowedModules' => $allowedModules,
                'isSuperAdmin' => $user ? $user->isSuperAdmin() : false,
                'isAdmin' => $user ? $user->isAdmin() : false,
                'hasAdministrativeRole' => $user ? $user->hasAdministrativeRole() : false,
            ],
            'tenant' => [
                'current' => $currentTenant,
                'available' => $availableTenants,
            ],
            'config' => ConfiguracionService::obtenerConfiguracionesPublicas(),
        ]);
    }
}
