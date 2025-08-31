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
        Schema::table('urna_sessions', function (Blueprint $table) {
            // Eliminar el constraint único problemático que incluye status
            // Esto estaba causando problemas al tener múltiples sesiones 'expired' para el mismo usuario/votación
            $table->dropUnique('unique_active_session');
            
            // NO crear nuevo índice único por ahora
            // El control se hará por lógica de aplicación para permitir múltiples sesiones expired/voted
            // Solo crear índice regular para mejorar performance de búsquedas
            $table->index(['votacion_id', 'usuario_id', 'status'], 'idx_votacion_usuario_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('urna_sessions', function (Blueprint $table) {
            // Eliminar el índice regular
            $table->dropIndex('idx_votacion_usuario_status');
            
            // Restaurar el índice único original (aunque problemático)
            $table->unique(['votacion_id', 'usuario_id', 'status'], 'unique_active_session');
        });
    }
};