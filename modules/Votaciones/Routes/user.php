<?php

use Illuminate\Support\Facades\Route;
use Modules\Votaciones\Http\Controllers\User;

Route::middleware(['auth', 'verified'])
    ->prefix('user/votaciones')
    ->name('user.votaciones.')
    ->group(function () {
        // Agregar rutas aquí
    });
