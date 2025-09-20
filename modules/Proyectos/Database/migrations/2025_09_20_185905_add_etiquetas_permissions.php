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
        // Permisos para gestión de etiquetas en proyectos
        $proyectosEtiquetasPermissions = [
            'proyectos.manage_tags' => 'Gestionar etiquetas de proyectos',
            'etiquetas.create' => 'Crear nuevas etiquetas',
            'etiquetas.edit' => 'Editar etiquetas existentes',
            'etiquetas.delete' => 'Eliminar etiquetas',
        ];

        // Permisos para categorías de etiquetas
        $categoriasPermissions = [
            'categorias_etiquetas.view' => 'Ver categorías de etiquetas',
            'categorias_etiquetas.create' => 'Crear categorías de etiquetas',
            'categorias_etiquetas.edit' => 'Editar categorías de etiquetas',
            'categorias_etiquetas.delete' => 'Eliminar categorías de etiquetas',
        ];

        // Crear permisos de etiquetas para proyectos
        foreach ($proyectosEtiquetasPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // Crear permisos de categorías
        foreach ($categoriasPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // Asignar permisos a roles existentes
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($proyectosEtiquetasPermissions));
            $adminRole->givePermissionTo(array_keys($categoriasPermissions));
        }

        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo([
                'proyectos.manage_tags',
                'etiquetas.create',
                'categorias_etiquetas.view',
            ]);
        }

        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            // Los usuarios regulares no tienen permisos de gestión de etiquetas por defecto
            // pero pueden ver las etiquetas en los proyectos (esto se controla a nivel de vista)
        }

        // El super_admin ya tiene todos los permisos automáticamente
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar permisos de etiquetas
        $permissions = [
            'proyectos.manage_tags',
            'etiquetas.create',
            'etiquetas.edit',
            'etiquetas.delete',
            'categorias_etiquetas.view',
            'categorias_etiquetas.create',
            'categorias_etiquetas.edit',
            'categorias_etiquetas.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::where('name', $permission)->delete();
        }
    }
};
