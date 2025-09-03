<?php

use Illuminate\Support\Facades\Route;
use Modules\Elecciones\Http\Controllers\User;

Route::middleware(['auth', 'verified'])
    ->prefix('user/elecciones')
    ->name('user.elecciones.')
    ->group(function () {
        // Agregar rutas aquí
    });
