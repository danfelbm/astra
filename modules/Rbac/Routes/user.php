<?php

use Illuminate\Support\Facades\Route;
use Modules\Rbac\Http\Controllers\User;

Route::middleware(['auth', 'verified'])
    ->prefix('user/rbac')
    ->name('user.rbac.')
    ->group(function () {
        // Agregar rutas aquí
    });
