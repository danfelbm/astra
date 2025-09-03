<?php

use Illuminate\Support\Facades\Route;
use Modules\Rbac\Http\Controllers\Api;

Route::middleware(['api'])
    ->prefix('api/rbac')
    ->name('api.rbac.')
    ->group(function () {
        // Agregar rutas API aquí
    });
