<?php

use Illuminate\Support\Facades\Route;
use Modules\Formularios\Http\Controllers\Api;

Route::middleware(['api'])
    ->prefix('api/formularios')
    ->name('api.formularios.')
    ->group(function () {
        // Agregar rutas API aquí
    });
