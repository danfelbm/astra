<?php

use Illuminate\Support\Facades\Route;
use Modules\Geografico\Http\Controllers\User;

Route::middleware(['auth', 'verified'])
    ->prefix('user/geografico')
    ->name('user.geografico.')
    ->group(function () {
        // Agregar rutas aquí
    });
