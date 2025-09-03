<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\User;

Route::middleware(['auth', 'verified'])
    ->prefix('user/users')
    ->name('user.users.')
    ->group(function () {
        // Agregar rutas aquí
    });
