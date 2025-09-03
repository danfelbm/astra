<?php

use Illuminate\Support\Facades\Route;
use Modules\Geografico\Http\Controllers\Api;

Route::middleware(['api'])
    ->prefix('api/geografico')
    ->name('api.geografico.')
    ->group(function () {
        // Agregar rutas API aquí
    });
