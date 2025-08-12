<?php

namespace App\Scopes;

use App\Services\TenantService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Aplicar el scope al query builder de Eloquent
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $tenantService = app(TenantService::class);
        $currentTenant = $tenantService->getCurrentTenant();
        
        // Si hay un tenant activo, filtrar por tenant_id
        if ($currentTenant) {
            $builder->where($model->getTable() . '.tenant_id', $currentTenant->id);
        }
    }
}