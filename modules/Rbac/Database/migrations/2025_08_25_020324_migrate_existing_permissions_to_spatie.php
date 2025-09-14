<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Hacer backup de la tabla role_user existente
        if (Schema::hasTable('role_user')) {
            Schema::rename('role_user', 'role_user_backup');
        }

        // 2. Crear permisos del módulo PeriodoElectoral en la tabla de Spatie
        $periodosPermisos = [
            'periodos.view' => 'Ver periodos electorales',
            'periodos.create' => 'Crear periodos electorales',
            'periodos.edit' => 'Editar periodos electorales',
            'periodos.delete' => 'Eliminar periodos electorales',
        ];

        foreach ($periodosPermisos as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // 3. Migrar datos de usuarios-roles desde el backup a la nueva tabla model_has_roles
        if (Schema::hasTable('role_user_backup')) {
            $roleUsers = DB::table('role_user_backup')->get();
            
            foreach ($roleUsers as $roleUser) {
                // Insertar en la nueva tabla model_has_roles de Spatie
                DB::table('model_has_roles')->insert([
                    'role_id' => $roleUser->role_id,
                    'model_type' => 'Modules\Core\Models\User',
                    'model_id' => $roleUser->user_id,
                ]);
            }
        }

        // 4. Migrar permisos desde el campo JSON de roles a la tabla role_has_permissions
        $roles = Role::all();
        
        foreach ($roles as $role) {
            if (!empty($role->permissions)) {
                // Decodificar JSON si es string, o usar directamente si es array
                $permissions = is_string($role->permissions) 
                    ? json_decode($role->permissions, true) 
                    : $role->permissions;
                
                if (is_array($permissions)) {
                    foreach ($permissions as $permissionName) {
                        // Buscar o crear el permiso
                        $permission = Permission::firstOrCreate(
                            ['name' => $permissionName, 'guard_name' => 'web']
                        );
                        
                        // Asignar el permiso al rol usando la tabla pivot
                        DB::table('role_has_permissions')->insertOrIgnore([
                            'permission_id' => $permission->id,
                            'role_id' => $role->id,
                        ]);
                    }
                }
            }
        }

        // 5. Añadir guard_name a los roles existentes si no lo tienen
        DB::table('roles')->whereNull('guard_name')->update(['guard_name' => 'web']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar la tabla role_user desde el backup
        if (Schema::hasTable('role_user_backup')) {
            if (Schema::hasTable('role_user')) {
                Schema::dropIfExists('role_user');
            }
            Schema::rename('role_user_backup', 'role_user');
        }

        // Limpiar los datos migrados de las tablas de Spatie
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();
        
        // Eliminar los permisos del módulo PeriodoElectoral
        Permission::whereIn('name', [
            'periodos.view',
            'periodos.create', 
            'periodos.edit',
            'periodos.delete'
        ])->delete();
    }
};
