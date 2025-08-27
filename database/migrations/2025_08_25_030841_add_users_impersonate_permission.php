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
        // Crear permiso faltante de impersonar usuarios
        Permission::firstOrCreate(
            ['name' => 'users.impersonate', 'guard_name' => 'web']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el permiso creado
        Permission::where('name', 'users.impersonate')->delete();
    }
};