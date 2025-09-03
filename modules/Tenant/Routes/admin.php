<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\Admin;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/tenant')
    ->name('admin.tenant.')
    ->group(function () {
        // Agregar rutas aquí
    });
