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
        // Crear permisos para el módulo de tenants
        $tenantPermissions = [
            'tenants.view' => 'Ver tenants',
            'tenants.create' => 'Crear tenants',
            'tenants.edit' => 'Editar tenants',
            'tenants.delete' => 'Eliminar tenants',
            'tenants.switch' => 'Cambiar entre tenants',
        ];

        foreach ($tenantPermissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name], [
                'guard_name' => 'web',
                'description' => $description
            ]);
        }

        // Asignar todos los permisos de tenants al rol super_admin
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(array_keys($tenantPermissions));
        }

        // Limpiar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tenantPermissions = [
            'tenants.view',
            'tenants.create',
            'tenants.edit',
            'tenants.delete',
            'tenants.switch',
        ];

        Permission::whereIn('name', $tenantPermissions)->delete();
        
        // Limpiar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};