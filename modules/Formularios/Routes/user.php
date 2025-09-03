<?php

use Illuminate\Support\Facades\Route;
use Modules\Formularios\Http\Controllers\User;

Route::middleware(['auth', 'verified'])
    ->prefix('user/formularios')
    ->name('user.formularios.')
    ->group(function () {
        // Agregar rutas aquí
    });
