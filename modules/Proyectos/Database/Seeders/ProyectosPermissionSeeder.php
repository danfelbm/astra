<?php

namespace Modules\Proyectos\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class ProyectosPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos para el módulo de Proyectos
        $permissions = [
            // Permisos administrativos
            'proyectos.view' => 'Ver proyectos',
            'proyectos.create' => 'Crear proyectos',
            'proyectos.edit' => 'Editar proyectos',
            'proyectos.delete' => 'Eliminar proyectos',
            'proyectos.manage_fields' => 'Gestionar campos personalizados',

            // Permisos de usuario
            'proyectos.view_own' => 'Ver proyectos propios',
            'proyectos.create_own' => 'Crear proyectos propios',
            'proyectos.edit_own' => 'Editar proyectos propios',

            // Permisos adicionales
            'proyectos.export' => 'Exportar proyectos',
            'proyectos.import' => 'Importar proyectos',
            'proyectos.view_reports' => 'Ver reportes de proyectos',
        ];

        // Crear los permisos
        foreach ($permissions as $permission => $description) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // Asignar permisos a roles existentes
        $this->assignPermissionsToRoles();
    }

    /**
     * Asigna los permisos a los roles existentes.
     */
    private function assignPermissionsToRoles(): void
    {
        // Permisos para Super Admin (tiene todos los permisos automáticamente)
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(Permission::where('name', 'like', 'proyectos.%')->get());
        }

        // Permisos para Admin
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $adminPermissions = [
                'proyectos.view',
                'proyectos.create',
                'proyectos.edit',
                'proyectos.delete',
                'proyectos.manage_fields',
                'proyectos.export',
                'proyectos.import',
                'proyectos.view_reports',
            ];
            $admin->givePermissionTo($adminPermissions);
        }

        // Permisos para Manager
        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            $managerPermissions = [
                'proyectos.view',
                'proyectos.create',
                'proyectos.edit',
                'proyectos.export',
                'proyectos.view_reports',
                'proyectos.view_own',
                'proyectos.edit_own',
            ];
            $manager->givePermissionTo($managerPermissions);
        }

        // Permisos para User
        $user = Role::where('name', 'user')->first();
        if ($user) {
            $userPermissions = [
                'proyectos.view_own',
                'proyectos.create_own',
                'proyectos.edit_own',
            ];
            $user->givePermissionTo($userPermissions);
        }

        // Permisos para Votante (solo lectura de proyectos públicos si aplica)
        $votante = Role::where('name', 'votante')->first();
        if ($votante) {
            $votantePermissions = [
                'proyectos.view_own',
            ];
            $votante->givePermissionTo($votantePermissions);
        }
    }
}