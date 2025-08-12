<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            // Agregar columnas timestamps
            $table->timestamps();
        });
        
        // Establecer valores predeterminados para registros existentes
        // Usamos la columna assigned_at existente como referencia
        \DB::statement('UPDATE role_user SET created_at = assigned_at, updated_at = assigned_at WHERE created_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            // Eliminar columnas timestamps
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};
