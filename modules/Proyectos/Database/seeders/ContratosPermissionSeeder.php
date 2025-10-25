<?php

namespace Modules\Proyectos\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class ContratosPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Crear permisos para contratos si no existen
            $permissions = [
                // Permisos administrativos
                'contratos.view' => 'Ver contratos',
                'contratos.create' => 'Crear contratos',
                'contratos.edit' => 'Editar contratos',
                'contratos.delete' => 'Eliminar contratos',
                'contratos.manage_fields' => 'Gestionar campos personalizados de contratos',
                'contratos.export' => 'Exportar contratos',
                'contratos.import' => 'Importar contratos',
                'contratos.change_status' => 'Cambiar estado de contratos',

                // Permisos de usuario
                'contratos.view_own' => 'Ver contratos propios',
                'contratos.edit_own' => 'Editar contratos propios',
                'contratos.view_public' => 'Ver contratos públicos',
                'contratos.download' => 'Descargar archivos de contratos',
            ];

            foreach ($permissions as $name => $description) {
                Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web']
                );
            }

            // Asignar permisos al rol super_admin
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole) {
                // Super admin obtiene todos los permisos
                $superAdminRole->givePermissionTo(Permission::all());
            }

            // Asignar permisos al rol admin
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $adminPermissions = [
                    'contratos.view',
                    'contratos.create',
                    'contratos.edit',
                    'contratos.delete',
                    'contratos.manage_fields',
                    'contratos.export',
                    'contratos.import',
                    'contratos.change_status',
                    'contratos.download',
                ];

                foreach ($adminPermissions as $permission) {
                    $adminRole->givePermissionTo($permission);
                }
            }

            // Asignar permisos al rol manager
            $managerRole = Role::where('name', 'manager')->first();
            if ($managerRole) {
                $managerPermissions = [
                    'contratos.view',
                    'contratos.create',
                    'contratos.edit',
                    'contratos.export',
                    'contratos.change_status',
                    'contratos.download',
                    'contratos.view_own',
                    'contratos.edit_own',
                ];

                foreach ($managerPermissions as $permission) {
                    $managerRole->givePermissionTo($permission);
                }
            }

            // Asignar permisos al rol user
            $userRole = Role::where('name', 'user')->first();
            if ($userRole) {
                $userPermissions = [
                    'contratos.view_own',
                    'contratos.view_public',
                    'contratos.download',
                ];

                foreach ($userPermissions as $permission) {
                    $userRole->givePermissionTo($permission);
                }
            }

            // Asignar permisos al rol votante (si existe)
            $votanteRole = Role::where('name', 'votante')->first();
            if ($votanteRole) {
                $votantePermissions = [
                    'contratos.view_public',
                ];

                foreach ($votantePermissions as $permission) {
                    $votanteRole->givePermissionTo($permission);
                }
            }

            // Crear rol específico para gestores de contratos (opcional)
            $contractManagerRole = Role::where('name', 'contract_manager')->first();

            if (!$contractManagerRole) {
                $contractManagerRole = Role::create([
                    'name' => 'contract_manager',
                    'display_name' => 'Gestor de Contratos',
                    'guard_name' => 'web',
                    'is_administrative' => true
                ]);
            }

            if ($contractManagerRole->wasRecentlyCreated) {
                $contractManagerPermissions = [
                    'contratos.view',
                    'contratos.create',
                    'contratos.edit',
                    'contratos.manage_fields',
                    'contratos.export',
                    'contratos.import',
                    'contratos.change_status',
                    'contratos.download',
                    'contratos.view_own',
                    'contratos.edit_own',
                ];

                foreach ($contractManagerPermissions as $permission) {
                    $contractManagerRole->givePermissionTo($permission);
                }

                echo "Rol 'contract_manager' creado y configurado.\n";
            }

            DB::commit();

            echo "Permisos de contratos configurados exitosamente.\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "Error configurando permisos de contratos: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}