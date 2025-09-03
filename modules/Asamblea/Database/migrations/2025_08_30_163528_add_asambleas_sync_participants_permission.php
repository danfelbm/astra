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
        // Crear el nuevo permiso
        $permission = Permission::create([
            'name' => 'asambleas.sync_participants',
            'guard_name' => 'web',
        ]);

        // Asignar el permiso a los roles que deben tenerlo
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permission);
        }

        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            $manager->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el permiso
        Permission::where('name', 'asambleas.sync_participants')->delete();
    }
};