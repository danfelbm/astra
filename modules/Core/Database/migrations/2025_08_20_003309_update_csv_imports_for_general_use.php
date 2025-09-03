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
        Schema::table('csv_imports', function (Blueprint $table) {
            // Hacer votacion_id nullable para permitir importaciones sin votación
            $table->foreignId('votacion_id')->nullable()->change();
            
            // Nuevos campos para importación general
            $table->string('name')->nullable()->after('original_filename'); // Nombre descriptivo de la importación
            $table->enum('import_type', ['votacion', 'users', 'general'])->default('votacion')->after('name');
            $table->enum('import_mode', ['insert', 'update', 'both'])->default('insert')->after('import_type');
            $table->json('field_mappings')->nullable()->after('import_mode'); // Mapeo columnas CSV -> campos modelo
            $table->json('update_fields')->nullable()->after('field_mappings'); // Campos a actualizar en modo update
            $table->json('conflict_resolution')->nullable()->after('update_fields'); // Conflictos pendientes de resolución
            
            // Índices para mejorar performance
            $table->index('import_type');
            $table->index(['import_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('csv_imports', function (Blueprint $table) {
            // Revertir votacion_id a no nullable
            $table->foreignId('votacion_id')->nullable(false)->change();
            
            // Eliminar nuevos campos
            $table->dropColumn([
                'name',
                'import_type', 
                'import_mode',
                'field_mappings',
                'update_fields',
                'conflict_resolution'
            ]);
            
            // Eliminar índices
            $table->dropIndex(['import_type']);
            $table->dropIndex(['import_type', 'status']);
        });
    }
};