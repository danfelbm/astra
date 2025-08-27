<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Core\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Permisos faltantes identificados en el análisis del módulo Candidaturas
        $permissions = [
            // Permisos para usuarios regulares (sus propias candidaturas)
            'candidaturas.view_own',
            'candidaturas.create_own', 
            'candidaturas.edit_own',
            
            // Permisos administrativos faltantes
            'candidaturas.configuracion',
            'candidaturas.reject',
            'candidaturas.comment',
            'candidaturas.recordatorios',
            'candidaturas.notificaciones',
            'candidaturas.edit',
            'candidaturas.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', [
            'candidaturas.view_own',
            'candidaturas.create_own',
            'candidaturas.edit_own',
            'candidaturas.configuracion',
            'candidaturas.reject',
            'candidaturas.comment',
            'candidaturas.recordatorios',
            'candidaturas.notificaciones',
            'candidaturas.edit',
            'candidaturas.delete',
        ])->delete();
    }
};