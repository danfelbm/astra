<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TenantService
{
    /**
     * La clave de sesión para almacenar el tenant actual
     */
    const SESSION_KEY = 'current_tenant_id';
    
    /**
     * Cache del tenant actual
     */
    protected ?Tenant $currentTenant = null;

    /**
     * Obtener el tenant actual
     */
    public function getCurrentTenant(): ?Tenant
    {
        // Si ya está en cache, devolverlo
        if ($this->currentTenant) {
            return $this->currentTenant;
        }

        // Intentar obtener de la sesión
        $tenantId = Session::get(self::SESSION_KEY);
        
        if ($tenantId) {
            $this->currentTenant = Tenant::find($tenantId);
            return $this->currentTenant;
        }

        // Si hay usuario autenticado, obtener su tenant
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->tenant_id) {
                $this->currentTenant = Tenant::find($user->tenant_id);
                if ($this->currentTenant) {
                    Session::put(self::SESSION_KEY, $this->currentTenant->id);
                }
                return $this->currentTenant;
            }
        }

        // Tenant por defecto (útil durante desarrollo)
        $defaultTenant = Tenant::first();
        if ($defaultTenant) {
            $this->setCurrentTenant($defaultTenant);
            return $defaultTenant;
        }

        return null;
    }

    /**
     * Establecer el tenant actual
     */
    public function setCurrentTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
        Session::put(self::SESSION_KEY, $tenant->id);
    }

    /**
     * Limpiar el tenant actual
     */
    public function clearCurrentTenant(): void
    {
        $this->currentTenant = null;
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Obtener tenant por subdomain
     */
    public function getTenantBySubdomain(string $subdomain): ?Tenant
    {
        return Tenant::where('subdomain', $subdomain)
                     ->where('active', true)
                     ->first();
    }

    /**
     * Verificar si el usuario puede acceder al tenant
     */
    public function userCanAccessTenant(User $user, Tenant $tenant): bool
    {
        // Super admin puede acceder a cualquier tenant
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Usuario regular solo puede acceder a su propio tenant
        return $user->tenant_id === $tenant->id;
    }

    /**
     * Cambiar al contexto de un tenant específico (para super admin)
     */
    public function switchToTenant(Tenant $tenant): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Verificar que el usuario pueda acceder al tenant
        if (!$this->userCanAccessTenant($user, $tenant)) {
            return false;
        }

        // Cambiar al nuevo tenant
        $this->setCurrentTenant($tenant);
        
        return true;
    }

    /**
     * Obtener todos los tenants disponibles para el usuario actual
     */
    public function getAvailableTenantsForUser(): \Illuminate\Database\Eloquent\Collection
    {
        $user = Auth::user();
        
        if (!$user) {
            return collect();
        }

        // Super admin puede ver todos los tenants
        if ($user->isSuperAdmin()) {
            return Tenant::active()->get();
        }

        // Usuario regular solo ve su propio tenant
        if ($user->tenant_id) {
            return Tenant::where('id', $user->tenant_id)
                        ->active()
                        ->get();
        }

        return collect();
    }

    /**
     * Verificar si el sistema está en modo multi-tenant
     */
    public function isMultiTenantEnabled(): bool
    {
        return Tenant::count() > 1;
    }

    /**
     * Verificar límites del tenant
     */
    public function checkTenantLimit(string $resource): bool
    {
        $tenant = $this->getCurrentTenant();
        
        if (!$tenant) {
            return false;
        }

        return $tenant->checkLimit($resource);
    }
}