<?php

// Script de prueba para verificar el nuevo permiso users.assign_roles

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "\n=== PRUEBA DEL SISTEMA DE PERMISOS users.assign_roles ===\n\n";

// Verificar configuración
$defaultRoleId = config('app.default_user_role_id');
echo "✓ Rol por defecto configurado: ID {$defaultRoleId}\n";

// Verificar que el permiso existe en la definición de permisos
$roleController = new \App\Http\Controllers\Admin\RoleController(app(\App\Services\TenantService::class));
$reflection = new ReflectionClass($roleController);
$method = $reflection->getMethod('getAvailablePermissions');
$method->setAccessible(true);
$permissions = $method->invoke($roleController);

$userPermissions = $permissions['administrative']['users']['permissions'] ?? [];
if (array_key_exists('users.assign_roles', $userPermissions)) {
    echo "✓ Permiso 'users.assign_roles' definido correctamente: {$userPermissions['users.assign_roles']}\n";
} else {
    echo "✗ Permiso 'users.assign_roles' NO encontrado en la definición\n";
}

// Obtener un usuario de prueba
$testUserId = 1; // Cambiar según necesidad
$testUser = User::find($testUserId);

if ($testUser) {
    echo "\n--- Verificando usuario de prueba (ID: {$testUserId}) ---\n";
    echo "Nombre: {$testUser->name}\n";
    echo "Email: {$testUser->email}\n";
    
    // Verificar roles del usuario
    $roles = $testUser->roles;
    echo "Roles asignados: ";
    foreach ($roles as $role) {
        echo "{$role->display_name} ({$role->name}) ";
    }
    echo "\n";
    
    // Verificar permiso específico
    $hasPermission = $testUser->hasPermission('users.assign_roles');
    echo "¿Tiene permiso 'users.assign_roles'? " . ($hasPermission ? "SÍ" : "NO") . "\n";
    
    // Verificar permisos de usuarios
    $canViewUsers = $testUser->hasPermission('users.view');
    $canCreateUsers = $testUser->hasPermission('users.create');
    $canEditUsers = $testUser->hasPermission('users.edit');
    $canAssignRoles = $testUser->hasPermission('users.assign_roles');
    
    echo "\nPermisos de usuarios:\n";
    echo "  - users.view: " . ($canViewUsers ? "✓" : "✗") . "\n";
    echo "  - users.create: " . ($canCreateUsers ? "✓" : "✗") . "\n";
    echo "  - users.edit: " . ($canEditUsers ? "✓" : "✗") . "\n";
    echo "  - users.assign_roles: " . ($canAssignRoles ? "✓" : "✗") . "\n";
    
    // Listar todos los permisos del usuario
    echo "\nTodos los permisos del usuario:\n";
    $allPermissions = [];
    foreach ($testUser->roles as $role) {
        if ($role->permissions) {
            $allPermissions = array_merge($allPermissions, $role->permissions);
        }
    }
    $allPermissions = array_unique($allPermissions);
    sort($allPermissions);
    
    foreach ($allPermissions as $permission) {
        echo "  - {$permission}\n";
    }
} else {
    echo "✗ Usuario de prueba (ID: {$testUserId}) no encontrado\n";
}

// Verificar roles del sistema
echo "\n--- Roles del sistema y sus permisos ---\n";
$systemRoles = Role::whereNull('tenant_id')
    ->orWhere('tenant_id', 1)
    ->orderBy('id')
    ->get();

foreach ($systemRoles as $role) {
    echo "\n{$role->display_name} (ID: {$role->id}, name: {$role->name}):\n";
    $hasAssignRoles = in_array('users.assign_roles', $role->permissions ?? []);
    echo "  ¿Tiene users.assign_roles? " . ($hasAssignRoles ? "SÍ" : "NO") . "\n";
    
    if ($hasAssignRoles || $role->name === 'super_admin') {
        echo "  ✓ Puede asignar roles a usuarios\n";
    } else {
        echo "  ✗ NO puede asignar roles (usará rol por defecto ID: {$defaultRoleId})\n";
    }
}

// Verificar el rol por defecto
$defaultRole = Role::find($defaultRoleId);
if ($defaultRole) {
    echo "\n--- Rol por defecto ---\n";
    echo "ID: {$defaultRole->id}\n";
    echo "Nombre: {$defaultRole->display_name} ({$defaultRole->name})\n";
    echo "Descripción: {$defaultRole->description}\n";
} else {
    echo "\n✗ ADVERTENCIA: Rol por defecto (ID: {$defaultRoleId}) no encontrado\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n\n";