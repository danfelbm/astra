<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear permisos para contratos
        $permissions = [
            // Permisos administrativos
            'contratos.view' => 'Ver contratos',
            'contratos.create' => 'Crear contratos',
            'contratos.edit' => 'Editar contratos',
            'contratos.delete' => 'Eliminar contratos',
            'contratos.manage_fields' => 'Gestionar campos personalizados de contratos',
            'contratos.export' => 'Exportar contratos',
            'contratos.change_status' => 'Cambiar estado de contratos',

            // Permisos de usuario
            'contratos.view_own' => 'Ver contratos propios',
            'contratos.edit_own' => 'Editar contratos propios',
            'contratos.view_public' => 'Ver contratos públicos',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // Asignar permisos a roles existentes
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'contratos.view',
                'contratos.create',
                'contratos.edit',
                'contratos.delete',
                'contratos.manage_fields',
                'contratos.export',
                'contratos.change_status',
            ]);
        }

        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo([
                'contratos.view',
                'contratos.create',
                'contratos.edit',
                'contratos.export',
            ]);
        }

        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $userRole->givePermissionTo([
                'contratos.view_own',
                'contratos.view_public',
            ]);
        }

        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(Permission::all());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar permisos de contratos
        $permissions = [
            'contratos.view',
            'contratos.create',
            'contratos.edit',
            'contratos.delete',
            'contratos.manage_fields',
            'contratos.export',
            'contratos.change_status',
            'contratos.view_own',
            'contratos.edit_own',
            'contratos.view_public',
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::where('name', $permission)->first();
            if ($perm) {
                $perm->delete();
            }
        }
    }
};
