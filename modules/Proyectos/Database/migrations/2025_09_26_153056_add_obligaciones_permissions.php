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
        // Crear permisos para obligaciones de contratos
        $permissions = [
            // Permisos administrativos
            'obligaciones.view' => 'Ver obligaciones de contratos',
            'obligaciones.create' => 'Crear obligaciones de contratos',
            'obligaciones.edit' => 'Editar obligaciones de contratos',
            'obligaciones.delete' => 'Eliminar obligaciones de contratos',
            'obligaciones.complete' => 'Marcar obligaciones como cumplidas',
            'obligaciones.export' => 'Exportar obligaciones de contratos',

            // Permisos de usuario
            'obligaciones.view_own' => 'Ver obligaciones propias',
            'obligaciones.complete_own' => 'Completar obligaciones propias',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // Asignar permisos a roles
        $adminPermissions = [
            'obligaciones.view',
            'obligaciones.create',
            'obligaciones.edit',
            'obligaciones.delete',
            'obligaciones.complete',
            'obligaciones.export',
        ];

        $userPermissions = [
            'obligaciones.view_own',
            'obligaciones.complete_own',
        ];

        // Asignar permisos al rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            foreach ($adminPermissions as $permission) {
                $adminRole->givePermissionTo($permission);
            }
        }

        // Asignar permisos al rol super_admin
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            foreach (array_merge($adminPermissions, $userPermissions) as $permission) {
                $superAdminRole->givePermissionTo($permission);
            }
        }

        // Asignar permisos al rol user
        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            foreach ($userPermissions as $permission) {
                $userRole->givePermissionTo($permission);
            }
        }

        // Asignar permisos al rol manager (si existe)
        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo('obligaciones.view');
            $managerRole->givePermissionTo('obligaciones.complete');
            $managerRole->givePermissionTo('obligaciones.view_own');
            $managerRole->givePermissionTo('obligaciones.complete_own');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'obligaciones.view',
            'obligaciones.create',
            'obligaciones.edit',
            'obligaciones.delete',
            'obligaciones.complete',
            'obligaciones.export',
            'obligaciones.view_own',
            'obligaciones.complete_own',
        ];

        foreach ($permissions as $permission) {
            Permission::where('name', $permission)->delete();
        }
    }
};
