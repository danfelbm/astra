<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas principales y se incluyen los archivos de rutas
| específicos por tipo de usuario (Guest, User, Admin).
|
*/

// Ruta principal - Página de bienvenida con redirección inteligente
Route::get('/', function () {
    $user = auth()->user();
    $redirectRoute = 'user.dashboard'; // Valor por defecto
    
    if ($user) {
        $userRoles = $user->roles()->pluck('name')->toArray();
        
        // Si tiene múltiples roles, priorizar según contexto o preferencia
        if (in_array('super_admin', $userRoles) || in_array('admin', $userRoles) || in_array('manager', $userRoles)) {
            $redirectRoute = 'admin.dashboard';
        } elseif (in_array('user', $userRoles)) {
            $redirectRoute = 'user.dashboard'; // Nueva ruta con prefijo
        }
    }
    
    return Inertia::render('Welcome', [
        'redirectRoute' => $redirectRoute
    ]);
})->name('home');

/*
|--------------------------------------------------------------------------
| Include Route Files by Environment
|--------------------------------------------------------------------------
|
| Incluir archivos de rutas separados por tipo de usuario para mejor
| organización y mantenimiento del código.
|
*/

// Rutas públicas (sin autenticación requerida)
require __DIR__.'/guest.php';

// Rutas para usuarios autenticados (no administrativos)
require __DIR__.'/user.php';

// Rutas administrativas (requieren permisos admin)
require __DIR__.'/admin.php';

// Rutas de configuración de usuario (ya existente)
require __DIR__.'/settings.php';

// Rutas de autenticación (ya existente)  
require __DIR__.'/auth.php';

// Test routes for debugging (remove in production)
if (file_exists(__DIR__.'/test.php')) {
    require __DIR__.'/test.php';
}