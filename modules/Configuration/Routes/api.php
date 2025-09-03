<?php

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Http\Controllers\Api;

Route::middleware(['api'])
    ->prefix('api/configuration')
    ->name('api.configuration.')
    ->group(function () {
        // Agregar rutas API aquí
    });
