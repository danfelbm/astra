<?php

namespace Modules\Proyectos\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class ProyectosCompletePermissionSeeder extends Seeder
{
    /**
     * Ejecutar el seeder con TODOS los permisos del módulo Proyectos.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Definir TODOS los permisos del módulo
            $allPermissions = [
                // ===== PROYECTOS =====
                'proyectos.view' => 'Ver proyectos',
                'proyectos.create' => 'Crear proyectos',
                'proyectos.edit' => 'Editar proyectos',
                'proyectos.delete' => 'Eliminar proyectos',
                'proyectos.view_own' => 'Ver proyectos propios',
                'proyectos.create_own' => 'Crear proyectos propios',
                'proyectos.edit_own' => 'Editar proyectos propios',
                'proyectos.delete_own' => 'Eliminar proyectos propios',
                'proyectos.export' => 'Exportar proyectos',
                'proyectos.import' => 'Importar proyectos',
                'proyectos.view_reports' => 'Ver reportes de proyectos',
                'proyectos.manage_tags' => 'Gestionar etiquetas de proyectos',
                'proyectos.manage_fields' => 'Gestionar campos personalizados',

                // ===== CATEGORÍAS Y ETIQUETAS =====
                'categorias_etiquetas.view' => 'Ver categorías de etiquetas',
                'categorias_etiquetas.create' => 'Crear categorías de etiquetas',
                'categorias_etiquetas.edit' => 'Editar categorías de etiquetas',
                'categorias_etiquetas.delete' => 'Eliminar categorías de etiquetas',

                'etiquetas.view' => 'Ver etiquetas',
                'etiquetas.create' => 'Crear etiquetas',
                'etiquetas.edit' => 'Editar etiquetas',
                'etiquetas.delete' => 'Eliminar etiquetas',

                // ===== CAMPOS PERSONALIZADOS =====
                'campos_personalizados.view' => 'Ver campos personalizados',
                'campos_personalizados.create' => 'Crear campos personalizados',
                'campos_personalizados.edit' => 'Editar campos personalizados',
                'campos_personalizados.delete' => 'Eliminar campos personalizados',

                // ===== CONTRATOS =====
                'contratos.view' => 'Ver contratos',
                'contratos.create' => 'Crear contratos',
                'contratos.edit' => 'Editar contratos',
                'contratos.delete' => 'Eliminar contratos',
                'contratos.view_own' => 'Ver contratos propios',
                'contratos.edit_own' => 'Editar contratos propios',
                'contratos.view_public' => 'Ver contratos públicos',
                'contratos.change_status' => 'Cambiar estado de contratos',
                'contratos.manage_fields' => 'Gestionar campos de contratos',
                'contratos.export' => 'Exportar contratos',
                'contratos.import' => 'Importar contratos',
                'contratos.download' => 'Descargar archivos de contratos',

                // ===== HITOS =====
                'hitos.view' => 'Ver hitos',
                'hitos.create' => 'Crear hitos',
                'hitos.edit' => 'Editar hitos',
                'hitos.delete' => 'Eliminar hitos',
                'hitos.view_own' => 'Ver hitos propios',
                'hitos.complete' => 'Completar hitos',
                'hitos.complete_own' => 'Completar hitos propios',
                'hitos.update_progress' => 'Actualizar progreso de hitos',
                'hitos.manage_deliverables' => 'Gestionar entregables de hitos',

                // ===== ENTREGABLES =====
                'entregables.view' => 'Ver entregables',
                'entregables.create' => 'Crear entregables',
                'entregables.edit' => 'Editar entregables',
                'entregables.delete' => 'Eliminar entregables',
                'entregables.assign' => 'Asignar entregables a usuarios',
                'entregables.complete' => 'Marcar entregables como completados',
            ];

            // Crear o actualizar permisos
            foreach ($allPermissions as $name => $description) {
                Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web']
                );
            }

            echo "✅ Permisos creados/actualizados: " . count($allPermissions) . PHP_EOL;

            // Asignar permisos a roles
            $this->assignPermissionsToRoles($allPermissions);

            DB::commit();

            echo "✅ Seeder ejecutado exitosamente" . PHP_EOL;

        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ Error: " . $e->getMessage() . PHP_EOL;
            throw $e;
        }
    }

    /**
     * Asigna los permisos a los roles existentes.
     */
    private function assignPermissionsToRoles(array $allPermissions): void
    {
        // Super Admin - Obtiene TODOS los permisos automáticamente
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            // Super admin siempre tiene todos los permisos disponibles
            $superAdmin->syncPermissions(Permission::all());
            echo "✅ Permisos asignados a super_admin" . PHP_EOL;
        }

        // Admin - Todos los permisos excepto los *_own (que son para usuarios regulares)
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            // IMPORTANTE: Obtener permisos EXISTENTES del admin para no sobrescribir otros módulos
            $existingPermissions = $admin->permissions()->pluck('name')->toArray();

            // Filtrar solo permisos del módulo Proyectos que no sean _own
            $proyectosPermissions = array_filter(array_keys($allPermissions), function($permission) {
                return !str_ends_with($permission, '_own');
            });

            // COMBINAR permisos existentes con los nuevos del módulo
            $allAdminPermissions = array_unique(array_merge($existingPermissions, $proyectosPermissions));

            $admin->syncPermissions($allAdminPermissions);
            echo "✅ Permisos del módulo Proyectos agregados a admin: " . count($proyectosPermissions) . PHP_EOL;
            echo "   Total permisos del admin: " . count($allAdminPermissions) . PHP_EOL;
        }

        // Manager - Permisos de gestión intermedios
        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            // IMPORTANTE: Obtener permisos EXISTENTES del manager
            $existingManagerPermissions = $manager->permissions()->pluck('name')->toArray();

            $managerPermissions = [
                // Proyectos
                'proyectos.view',
                'proyectos.create',
                'proyectos.edit',
                'proyectos.export',
                'proyectos.view_reports',
                'proyectos.view_own',
                'proyectos.edit_own',
                'proyectos.manage_tags',

                // Etiquetas
                'etiquetas.view',
                'etiquetas.create',
                'etiquetas.edit',
                'categorias_etiquetas.view',

                // Contratos
                'contratos.view',
                'contratos.create',
                'contratos.edit',
                'contratos.view_own',
                'contratos.edit_own',
                'contratos.export',

                // Hitos
                'hitos.view',
                'hitos.create',
                'hitos.edit',
                'hitos.view_own',
                'hitos.complete_own',
                'hitos.update_progress',
                'hitos.manage_deliverables',

                // Entregables
                'entregables.view',
                'entregables.create',
                'entregables.edit',
                'entregables.assign',
                'entregables.complete',
            ];

            // COMBINAR permisos existentes con los nuevos del módulo
            $allManagerPermissions = array_unique(array_merge($existingManagerPermissions, $managerPermissions));

            $manager->syncPermissions($allManagerPermissions);
            echo "✅ Permisos del módulo Proyectos agregados a manager: " . count($managerPermissions) . PHP_EOL;
            echo "   Total permisos del manager: " . count($allManagerPermissions) . PHP_EOL;
        }

        // User - Permisos básicos y *_own
        $user = Role::where('name', 'user')->first();
        if ($user) {
            // IMPORTANTE: Obtener permisos EXISTENTES del user
            $existingUserPermissions = $user->permissions()->pluck('name')->toArray();

            $userPermissions = [
                // Proyectos
                'proyectos.view_own',
                'proyectos.create_own',
                'proyectos.edit_own',
                'proyectos.delete_own',

                // Etiquetas (solo ver)
                'etiquetas.view',
                'categorias_etiquetas.view',

                // Contratos
                'contratos.view_own',
                'contratos.view_public',

                // Hitos
                'hitos.view_own',
                'hitos.complete_own',

                // Entregables
                'entregables.view',
            ];

            // COMBINAR permisos existentes con los nuevos del módulo
            $allUserPermissions = array_unique(array_merge($existingUserPermissions, $userPermissions));

            $user->syncPermissions($allUserPermissions);
            echo "✅ Permisos del módulo Proyectos agregados a user: " . count($userPermissions) . PHP_EOL;
            echo "   Total permisos del user: " . count($allUserPermissions) . PHP_EOL;
        }

        // Votante - Permisos mínimos de lectura
        $votante = Role::where('name', 'votante')->first();
        if ($votante) {
            // IMPORTANTE: Obtener permisos EXISTENTES del votante
            $existingVotantePermissions = $votante->permissions()->pluck('name')->toArray();

            $votantePermissions = [
                'proyectos.view_own',
                'contratos.view_public',
                'hitos.view_own',
                'entregables.view',
            ];

            // COMBINAR permisos existentes con los nuevos del módulo
            $allVotantePermissions = array_unique(array_merge($existingVotantePermissions, $votantePermissions));

            $votante->syncPermissions($allVotantePermissions);
            echo "✅ Permisos del módulo Proyectos agregados a votante: " . count($votantePermissions) . PHP_EOL;
            echo "   Total permisos del votante: " . count($allVotantePermissions) . PHP_EOL;
        }
    }
}