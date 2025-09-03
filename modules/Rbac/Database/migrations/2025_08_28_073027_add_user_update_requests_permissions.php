<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear los nuevos permisos para solicitudes de actualización de usuarios
        $permissions = [
            'users.update_requests' => 'Ver solicitudes de actualización de usuarios',
            'users.approve_updates' => 'Aprobar/rechazar actualizaciones de usuarios',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // Asignar automáticamente estos permisos al rol super_admin si existe
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(array_keys($permissions));
        }

        // Asignar automáticamente estos permisos al rol admin si existe
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($permissions));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar los permisos
        $permissions = [
            'users.update_requests',
            'users.approve_updates',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};