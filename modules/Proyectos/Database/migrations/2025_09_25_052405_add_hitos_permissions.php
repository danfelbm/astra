<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear permisos para hitos
        $permissions = [
            // Permisos administrativos
            'hitos.view' => 'Ver hitos',
            'hitos.create' => 'Crear hitos',
            'hitos.edit' => 'Editar hitos',
            'hitos.delete' => 'Eliminar hitos',
            'hitos.manage_deliverables' => 'Gestionar entregables',

            // Permisos de usuario
            'hitos.view_own' => 'Ver hitos propios',
            'hitos.complete_own' => 'Completar entregables propios',
            'hitos.update_progress' => 'Actualizar progreso de hitos',

            // Permisos para entregables
            'entregables.view' => 'Ver entregables',
            'entregables.create' => 'Crear entregables',
            'entregables.edit' => 'Editar entregables',
            'entregables.delete' => 'Eliminar entregables',
            'entregables.assign' => 'Asignar entregables a usuarios',
            'entregables.complete' => 'Marcar entregables como completados',
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
            $adminRole->givePermissionTo(array_keys($permissions));
        }

        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo([
                'hitos.view',
                'hitos.create',
                'hitos.edit',
                'hitos.manage_deliverables',
                'hitos.view_own',
                'hitos.complete_own',
                'hitos.update_progress',
                'entregables.view',
                'entregables.create',
                'entregables.edit',
                'entregables.assign',
                'entregables.complete',
            ]);
        }

        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $userRole->givePermissionTo([
                'hitos.view_own',
                'hitos.complete_own',
                'entregables.view',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'hitos.view',
            'hitos.create',
            'hitos.edit',
            'hitos.delete',
            'hitos.manage_deliverables',
            'hitos.view_own',
            'hitos.complete_own',
            'hitos.update_progress',
            'entregables.view',
            'entregables.create',
            'entregables.edit',
            'entregables.delete',
            'entregables.assign',
            'entregables.complete',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};
