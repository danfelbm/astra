<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\Api;

Route::middleware(['api'])
    ->prefix('api/users')
    ->name('api.users.')
    ->group(function () {
        // Agregar rutas API aquí
    });
