<?php

namespace App\Http\Middleware\Core;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use App\Services\Core\ConfiguracionService;
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

        // Cargar roles y relaciones geográficas del usuario si está autenticado
        $user = $request->user();
        if ($user) {
            $user->load(['roles', 'territorio', 'departamento', 'municipio', 'localidad']);
        }

        // Obtener información del tenant actual y disponibles para super admin
        $currentTenant = null;
        $availableTenants = null;
        if ($user && $user->hasRole('super_admin')) {
            // Para super admin, obtener el tenant actual desde el servicio
            if (app()->bound(\App\Services\Core\TenantService::class)) {
                $tenantService = app(\App\Services\Core\TenantService::class);
                $currentTenant = $tenantService->getCurrentTenant();
                $availableTenants = \App\Models\Tenant::where('active', true)
                    ->select(['id', 'name', 'subdomain', 'active', 'subscription_plan'])
                    ->orderBy('name')
                    ->get();
            }
        } else if ($user) {
            // Para usuarios regulares, solo el tenant actual si existe
            if (app()->bound(\App\Services\Core\TenantService::class)) {
                $tenantService = app(\App\Services\Core\TenantService::class);
                $currentTenant = $tenantService->getCurrentTenant();
            }
        }

        // Obtener todos los permisos del usuario usando Spatie
        $permissions = [];
        $allowedModules = [];
        if ($user) {
            // Obtener todos los permisos del usuario (directos + a través de roles)
            // Spatie devuelve una Collection, necesitamos los nombres como array
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            // Derivar los módulos permitidos de los permisos
            // Extraer la parte antes del punto (ej: "users.view" -> "users")
            $modules = [];
            foreach ($permissions as $permission) {
                $parts = explode('.', $permission);
                if (count($parts) > 0) {
                    $modules[] = $parts[0];
                }
            }
            // Eliminar duplicados y ordenar
            $allowedModules = array_values(array_unique($modules));
            sort($allowedModules);
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
                'isSuperAdmin' => $user ? $user->hasRole('super_admin') : false,
                'isAdmin' => $user ? $user->hasAnyRole(['admin', 'super_admin']) : false,
                'hasAdministrativeRole' => $user ? $user->hasAdministrativeAccess() : false,
            ],
            'tenant' => [
                'current' => $currentTenant,
                'available' => $availableTenants,
            ],
            'config' => ConfiguracionService::obtenerConfiguracionesPublicas(),
        ]);
    }
}
