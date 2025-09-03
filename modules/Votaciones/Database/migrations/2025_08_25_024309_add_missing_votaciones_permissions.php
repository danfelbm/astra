<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear permiso faltante de votaciones
        Permission::firstOrCreate(
            ['name' => 'votaciones.view_results', 'guard_name' => 'web']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el permiso creado
        Permission::where('name', 'votaciones.view_results')->delete();
    }
};