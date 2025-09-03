<?php

use Illuminate\Support\Facades\Route;
use Modules\Configuration\Http\Controllers\User;

Route::middleware(['auth', 'verified'])
    ->prefix('user/configuration')
    ->name('user.configuration.')
    ->group(function () {
        // Agregar rutas aquí
    });
