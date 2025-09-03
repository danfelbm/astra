<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\User;

Route::middleware(['auth', 'verified'])
    ->prefix('user/tenant')
    ->name('user.tenant.')
    ->group(function () {
        // Agregar rutas aquí
    });
