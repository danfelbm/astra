<?php

namespace Modules\Campanas\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CampanasPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resetear caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos para plantillas
        $permisosPlantillas = [
            'campanas.plantillas.view' => 'Ver plantillas de campañas',
            'campanas.plantillas.create' => 'Crear plantillas de campañas',
            'campanas.plantillas.edit' => 'Editar plantillas de campañas',
            'campanas.plantillas.delete' => 'Eliminar plantillas de campañas',
        ];

        // Crear permisos para campañas
        $permisosCampanas = [
            'campanas.view' => 'Ver campañas',
            'campanas.create' => 'Crear campañas',
            'campanas.edit' => 'Editar campañas',
            'campanas.delete' => 'Eliminar campañas',
            'campanas.send' => 'Enviar campañas',
            'campanas.pause' => 'Pausar campañas',
            'campanas.resume' => 'Reanudar campañas',
            'campanas.cancel' => 'Cancelar campañas',
            'campanas.export' => 'Exportar reportes de campañas',
        ];

        // Crear todos los permisos
        $todosPermisos = array_merge($permisosPlantillas, $permisosCampanas);
        
        foreach ($todosPermisos as $permiso => $descripcion) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web'
            ]);
        }

        // Asignar permisos al rol admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($todosPermisos));
        }

        // Asignar permisos al rol super_admin
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(array_keys($todosPermisos));
        }

        // Asignar permisos básicos al rol manager (si existe)
        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo([
                'campanas.plantillas.view',
                'campanas.view',
                'campanas.export',
            ]);
        }

        echo "✅ Permisos del módulo Campañas creados exitosamente.\n";
    }
}