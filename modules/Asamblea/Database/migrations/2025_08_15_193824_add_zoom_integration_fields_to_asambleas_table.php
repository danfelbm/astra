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
        Schema::table('asambleas', function (Blueprint $table) {
            // Tipo de integración de Zoom (SDK o API)
            $table->enum('zoom_integration_type', ['sdk', 'api'])
                  ->default('sdk')
                  ->after('zoom_start_url');
            
            // Campo para occurrence IDs cuando se usa API
            $table->string('zoom_occurrence_ids')
                  ->nullable()
                  ->after('zoom_integration_type')
                  ->comment('IDs de ocurrencias separados por comas para API de Zoom');
            
            // Índice para optimizar consultas por tipo de integración
            $table->index(['zoom_enabled', 'zoom_integration_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asambleas', function (Blueprint $table) {
            // Eliminar índice primero
            $table->dropIndex(['zoom_enabled', 'zoom_integration_type']);
            
            // Eliminar campos añadidos
            $table->dropColumn([
                'zoom_integration_type',
                'zoom_occurrence_ids'
            ]);
        });
    }
};
