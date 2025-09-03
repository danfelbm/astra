<?php

use Illuminate\Support\Facades\Route;
use Modules\Rbac\Http\Controllers\Admin;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/rbac')
    ->name('admin.rbac.')
    ->group(function () {
        // Agregar rutas aquí
    });
