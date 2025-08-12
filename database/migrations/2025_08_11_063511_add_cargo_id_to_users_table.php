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
        Schema::table('users', function (Blueprint $table) {
            // Agregar campo cargo_id después de localidad_id si no existe
            if (!Schema::hasColumn('users', 'cargo_id')) {
                $table->unsignedBigInteger('cargo_id')->nullable()->after('localidad_id');
                
                // Agregar foreign key constraint
                $table->foreign('cargo_id')
                      ->references('id')
                      ->on('cargos')
                      ->onDelete('set null');
                      
                // Agregar índice para mejorar rendimiento
                $table->index('cargo_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Primero eliminar la foreign key constraint
            $table->dropForeign(['cargo_id']);
            
            // Luego eliminar el índice
            $table->dropIndex(['cargo_id']);
            
            // Finalmente eliminar la columna
            $table->dropColumn('cargo_id');
        });
    }
};