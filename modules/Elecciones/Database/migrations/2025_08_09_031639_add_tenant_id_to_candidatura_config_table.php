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
        // Verificar si la columna no existe antes de agregarla
        if (!Schema::hasColumn('candidatura_config', 'tenant_id')) {
            Schema::table('candidatura_config', function (Blueprint $table) {
                // Agregar columna tenant_id
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                
                // Crear foreign key
                $table->foreign('tenant_id')
                      ->references('id')
                      ->on('tenants')
                      ->onDelete('cascade');
                
                // Crear índice
                $table->index('tenant_id');
            });
            
            // Asignar tenant_id = 1 a todos los registros existentes
            \DB::table('candidatura_config')->whereNull('tenant_id')->update(['tenant_id' => 1]);
            
            // Hacer el campo NOT NULL después de asignar valores
            Schema::table('candidatura_config', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidatura_config', function (Blueprint $table) {
            // Eliminar foreign key
            $table->dropForeign(['tenant_id']);
            
            // Eliminar índice
            $table->dropIndex(['tenant_id']);
            
            // Eliminar columna
            $table->dropColumn('tenant_id');
        });
    }
};
