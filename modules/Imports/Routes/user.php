<?php

use Illuminate\Support\Facades\Route;
use Modules\Imports\Http\Controllers\User;

Route::middleware(['auth', 'verified'])
    ->prefix('user/imports')
    ->name('user.imports.')
    ->group(function () {
        // Agregar rutas aquí
    });
