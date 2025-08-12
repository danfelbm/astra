<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Obtener los roles que necesitan permisos de asambleas
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        $managerRole = DB::table('roles')->where('name', 'manager')->first();
        
        // Permisos de asambleas
        $asambleasPermissions = [
            'asambleas.view',
            'asambleas.create',
            'asambleas.edit',
            'asambleas.delete',
            'asambleas.manage_participants'
        ];
        
        // Actualizar permisos del rol admin
        if ($adminRole) {
            $currentPermissions = json_decode($adminRole->permissions, true) ?? [];
            $updatedPermissions = array_unique(array_merge($currentPermissions, $asambleasPermissions));
            
            // También añadir asambleas a los módulos permitidos
            $currentModules = json_decode($adminRole->allowed_modules, true) ?? [];
            if (!in_array('asambleas', $currentModules)) {
                $currentModules[] = 'asambleas';
            }
            
            DB::table('roles')
                ->where('id', $adminRole->id)
                ->update([
                    'permissions' => json_encode($updatedPermissions),
                    'allowed_modules' => json_encode($currentModules),
                    'updated_at' => now()
                ]);
        }
        
        // Actualizar permisos del rol manager (solo visualización)
        if ($managerRole) {
            $currentPermissions = json_decode($managerRole->permissions, true) ?? [];
            $managerAsambleasPermissions = ['asambleas.view'];
            $updatedPermissions = array_unique(array_merge($currentPermissions, $managerAsambleasPermissions));
            
            // También añadir asambleas a los módulos permitidos
            $currentModules = json_decode($managerRole->allowed_modules, true) ?? [];
            if (!in_array('asambleas', $currentModules)) {
                $currentModules[] = 'asambleas';
            }
            
            DB::table('roles')
                ->where('id', $managerRole->id)
                ->update([
                    'permissions' => json_encode($updatedPermissions),
                    'allowed_modules' => json_encode($currentModules),
                    'updated_at' => now()
                ]);
        }
        
        // El super_admin ya tiene todos los permisos por defecto ('*')
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Obtener los roles
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        $managerRole = DB::table('roles')->where('name', 'manager')->first();
        
        $asambleasPermissions = [
            'asambleas.view',
            'asambleas.create',
            'asambleas.edit',
            'asambleas.delete',
            'asambleas.manage_participants'
        ];
        
        // Remover permisos del rol admin
        if ($adminRole) {
            $currentPermissions = json_decode($adminRole->permissions, true) ?? [];
            $updatedPermissions = array_values(array_diff($currentPermissions, $asambleasPermissions));
            
            // Remover asambleas de los módulos permitidos
            $currentModules = json_decode($adminRole->allowed_modules, true) ?? [];
            $currentModules = array_values(array_diff($currentModules, ['asambleas']));
            
            DB::table('roles')
                ->where('id', $adminRole->id)
                ->update([
                    'permissions' => json_encode($updatedPermissions),
                    'allowed_modules' => json_encode($currentModules),
                    'updated_at' => now()
                ]);
        }
        
        // Remover permisos del rol manager
        if ($managerRole) {
            $currentPermissions = json_decode($managerRole->permissions, true) ?? [];
            $updatedPermissions = array_values(array_diff($currentPermissions, $asambleasPermissions));
            
            // Remover asambleas de los módulos permitidos
            $currentModules = json_decode($managerRole->allowed_modules, true) ?? [];
            $currentModules = array_values(array_diff($currentModules, ['asambleas']));
            
            DB::table('roles')
                ->where('id', $managerRole->id)
                ->update([
                    'permissions' => json_encode($updatedPermissions),
                    'allowed_modules' => json_encode($currentModules),
                    'updated_at' => now()
                ]);
        }
    }
};