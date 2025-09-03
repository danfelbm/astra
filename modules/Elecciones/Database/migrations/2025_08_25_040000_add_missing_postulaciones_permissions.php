<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear permisos faltantes para el módulo de postulaciones
        $permissions = [
            'postulaciones.edit',    // Editar postulaciones (admin)
            'postulaciones.delete',  // Eliminar postulaciones (admin)
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', [
            'postulaciones.edit',
            'postulaciones.delete',
        ])->delete();
    }
};