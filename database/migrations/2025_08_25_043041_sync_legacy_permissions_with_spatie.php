<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sincronizar los permisos del campo legacy_permissions con Spatie
        $roles = Role::all();
        
        foreach ($roles as $role) {
            $legacyPermissions = $role->legacy_permissions ?? [];
            
            if (empty($legacyPermissions)) {
                continue;
            }
            
            // Para cada permiso en el campo JSON legacy
            foreach ($legacyPermissions as $permissionName) {
                // Verificar si el permiso existe en la tabla de permisos de Spatie
                $permission = Permission::where('name', $permissionName)
                    ->where('guard_name', 'web')
                    ->first();
                
                if ($permission) {
                    // Si el permiso existe, asignarlo al rol usando Spatie
                    try {
                        if (!$role->hasPermissionTo($permission)) {
                            $role->givePermissionTo($permission);
                        }
                    } catch (\Exception $e) {
                        // Log pero continuar con otros permisos
                        \Log::warning("No se pudo sincronizar permiso {$permissionName} para rol {$role->name}: " . $e->getMessage());
                    }
                }
            }
        }
        
        // También sincronizar los permisos especiales de los módulos
        foreach ($roles as $role) {
            $allowedModules = $role->allowed_modules ?? [];
            
            // Si tiene módulos permitidos, intentar mapearlos a permisos
            foreach ($allowedModules as $module) {
                // Mapear módulos a permisos básicos de visualización
                $modulePermissionMap = [
                    'users' => 'users.view',
                    'votaciones' => 'votaciones.view',
                    'asambleas' => 'asambleas.view',
                    'convocatorias' => 'convocatorias.view',
                    'postulaciones' => 'postulaciones.view',
                    'candidaturas' => 'candidaturas.view',
                    'formularios' => 'formularios.view',
                    'cargos' => 'cargos.view',
                    'periodos' => 'periodos.view',
                    'reports' => 'reports.view',
                    'settings' => 'settings.view',
                    'roles' => 'roles.view',
                    'segments' => 'segments.view',
                    'dashboard' => 'dashboard.view',
                ];
                
                if (isset($modulePermissionMap[$module])) {
                    $permissionName = $modulePermissionMap[$module];
                    $permission = Permission::where('name', $permissionName)
                        ->where('guard_name', 'web')
                        ->first();
                    
                    if ($permission && !$role->hasPermissionTo($permission)) {
                        try {
                            $role->givePermissionTo($permission);
                        } catch (\Exception $e) {
                            \Log::warning("No se pudo sincronizar permiso de módulo {$module} para rol {$role->name}");
                        }
                    }
                }
            }
        }
        
        \Log::info('Sincronización de permisos legacy con Spatie completada');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hay rollback para esta migración ya que es una sincronización
        // Los permisos de Spatie permanecerán
        \Log::info('Rollback de sincronización de permisos no aplicable');
    }
};