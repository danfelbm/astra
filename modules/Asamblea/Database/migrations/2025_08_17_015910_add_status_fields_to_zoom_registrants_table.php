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
        Schema::table('zoom_registrants', function (Blueprint $table) {
            // Campo de estado para trackear el progreso del registro
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending')
                  ->after('registered_at');
            
            // Mensaje de error para registros fallidos
            $table->text('error_message')->nullable()->after('status');
            
            // Timestamp de cuando se inició el procesamiento
            $table->timestamp('processing_started_at')->nullable()->after('error_message');
            
            // Hacer nullable los campos de Zoom para permitir registros pendientes
            $table->string('zoom_registrant_id')->nullable()->change();
            $table->text('zoom_join_url')->nullable()->change();
            
            // Índices para optimización de consultas
            $table->index('status', 'idx_zoom_registrants_status');
            $table->index(['asamblea_id', 'status'], 'idx_zoom_registrants_asamblea_status');
            $table->index(['status', 'processing_started_at'], 'idx_zoom_registrants_status_processing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zoom_registrants', function (Blueprint $table) {
            // Eliminar índices primero
            $table->dropIndex('idx_zoom_registrants_status');
            $table->dropIndex('idx_zoom_registrants_asamblea_status');
            $table->dropIndex('idx_zoom_registrants_status_processing');
            
            // Revertir cambios en campos de Zoom
            $table->string('zoom_registrant_id')->nullable(false)->change();
            $table->text('zoom_join_url')->nullable(false)->change();
            
            // Luego eliminar las columnas
            $table->dropColumn(['status', 'error_message', 'processing_started_at']);
        });
    }
};
