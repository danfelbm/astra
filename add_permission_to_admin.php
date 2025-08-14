<?php

// Script para agregar el permiso users.assign_roles al rol Admin

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Role;

echo "\n=== Agregando permiso users.assign_roles al rol Admin ===\n\n";

// Buscar el rol Admin
$adminRole = Role::where('name', 'admin')->first();

if ($adminRole) {
    echo "Rol Admin encontrado (ID: {$adminRole->id})\n";
    echo "Permisos actuales: " . count($adminRole->permissions ?? []) . " permisos\n";
    
    // Agregar el nuevo permiso si no existe
    $permissions = $adminRole->permissions ?? [];
    
    if (!in_array('users.assign_roles', $permissions)) {
        $permissions[] = 'users.assign_roles';
        $adminRole->permissions = $permissions;
        $adminRole->save();
        
        echo "✓ Permiso 'users.assign_roles' agregado al rol Admin\n";
        echo "Nuevos permisos: " . count($adminRole->permissions) . " permisos\n";
    } else {
        echo "✓ El rol Admin ya tiene el permiso 'users.assign_roles'\n";
    }
    
    // Mostrar todos los permisos de usuarios
    echo "\nPermisos de usuarios del rol Admin:\n";
    foreach ($adminRole->permissions as $permission) {
        if (strpos($permission, 'users.') === 0) {
            echo "  - {$permission}\n";
        }
    }
} else {
    echo "✗ Rol Admin no encontrado\n";
}

echo "\n=== Completado ===\n";