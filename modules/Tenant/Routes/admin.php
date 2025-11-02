<?php

use Modules\Tenant\Http\Controllers\Admin\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Admin Routes
|--------------------------------------------------------------------------
|
| Rutas administrativas para gestión de tenants (multi-tenancy).
| Solo accesibles para super admin.
|
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Tenants routes (solo super admin) - Expandido para usar Spatie
        Route::get('tenants', [TenantController::class, 'index'])
            ->middleware('can:tenants.view')
            ->name('tenants.index');
        Route::get('tenants/create', [TenantController::class, 'create'])
            ->middleware('can:tenants.create')
            ->name('tenants.create');
        Route::post('tenants', [TenantController::class, 'store'])
            ->middleware('can:tenants.create')
            ->name('tenants.store');
        Route::get('tenants/{tenant}', [TenantController::class, 'show'])
            ->middleware('can:tenants.view')
            ->name('tenants.show');
        Route::get('tenants/{tenant}/edit', [TenantController::class, 'edit'])
            ->middleware('can:tenants.edit')
            ->name('tenants.edit');
        Route::put('tenants/{tenant}', [TenantController::class, 'update'])
            ->middleware('can:tenants.edit')
            ->name('tenants.update');
        Route::delete('tenants/{tenant}', [TenantController::class, 'destroy'])
            ->middleware('can:tenants.delete')
            ->name('tenants.destroy');
        Route::post('tenants/switch', [TenantController::class, 'switch'])
            ->middleware('can:tenants.switch')
            ->name('tenants.switch');
    });
