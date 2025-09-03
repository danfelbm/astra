<?php

use Illuminate\Support\Facades\Route;
use Modules\Imports\Http\Controllers\Api;

Route::middleware(['api'])
    ->prefix('api/imports')
    ->name('api.imports.')
    ->group(function () {
        // Agregar rutas API aquí
    });
