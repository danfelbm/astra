<?php

use Illuminate\Support\Facades\Route;
use Modules\Elecciones\Http\Controllers\Api;

Route::middleware(['api'])
    ->prefix('api/elecciones')
    ->name('api.elecciones.')
    ->group(function () {
        // Agregar rutas API aquí
    });
