<?php

namespace Modules\Comentarios\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ComentariosPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resetear caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos del módulo Comentarios
        $permisos = [
            'comentarios.view' => 'Ver comentarios',
            'comentarios.create' => 'Crear comentarios',
            'comentarios.edit_own' => 'Editar comentarios propios (24h)',
            'comentarios.delete_own' => 'Eliminar comentarios propios',
            'comentarios.delete_any' => 'Eliminar cualquier comentario',
            'comentarios.react' => 'Agregar reacciones a comentarios',
        ];

        // Crear todos los permisos
        foreach ($permisos as $permiso => $descripcion) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web'
            ]);
        }

        // Asignar todos los permisos al rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($permisos));
        }

        // Asignar todos los permisos al rol super_admin
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(array_keys($permisos));
        }

        // Permisos básicos para usuarios regulares (manager)
        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo([
                'comentarios.view',
                'comentarios.create',
                'comentarios.edit_own',
                'comentarios.delete_own',
                'comentarios.react',
            ]);
        }

        // Permisos básicos para usuarios regulares (user)
        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $userRole->givePermissionTo([
                'comentarios.view',
                'comentarios.create',
                'comentarios.edit_own',
                'comentarios.delete_own',
                'comentarios.react',
            ]);
        }

        echo "✅ Permisos del módulo Comentarios creados exitosamente.\n";
    }
}
