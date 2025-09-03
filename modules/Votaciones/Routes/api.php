<?php

use Illuminate\Support\Facades\Route;
use Modules\Votaciones\Http\Controllers\Api;

Route::middleware(['api'])
    ->prefix('api/votaciones')
    ->name('api.votaciones.')
    ->group(function () {
        // Agregar rutas API aquí
    });
