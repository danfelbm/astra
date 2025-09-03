<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\Api;

Route::middleware(['api'])
    ->prefix('api/tenant')
    ->name('api.tenant.')
    ->group(function () {
        // Agregar rutas API aquí
    });
