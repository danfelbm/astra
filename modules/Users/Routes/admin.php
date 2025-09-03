<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\Admin;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin/users')
    ->name('admin.users.')
    ->group(function () {
        // Agregar rutas aquí
    });
