<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Model;

trait HasTenant
{
    /**
     * Boot del trait para aplicar el global scope automáticamente
     */
    public static function bootHasTenant(): void
    {
        // Aplicar el global scope para filtrar por tenant
        static::addGlobalScope(new TenantScope);

        // Auto-asignar tenant_id al crear nuevos registros
        static::creating(function (Model $model) {
            if (!$model->tenant_id) {
                $tenantService = app(TenantService::class);
                $currentTenant = $tenantService->getCurrentTenant();
                
                if ($currentTenant) {
                    $model->tenant_id = $currentTenant->id;
                }
            }
        });
    }

    /**
     * Relación con el tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope para obtener registros de un tenant específico
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para obtener registros sin filtro de tenant (para super admin)
     */
    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Verificar si el registro pertenece al tenant actual
     */
    public function belongsToCurrentTenant(): bool
    {
        $tenantService = app(TenantService::class);
        $currentTenant = $tenantService->getCurrentTenant();
        
        return $currentTenant && $this->tenant_id === $currentTenant->id;
    }

    /**
     * Obtener el tenant_id actual
     */
    public static function getCurrentTenantId(): ?int
    {
        $tenantService = app(TenantService::class);
        $currentTenant = $tenantService->getCurrentTenant();
        
        return $currentTenant?->id;
    }
}