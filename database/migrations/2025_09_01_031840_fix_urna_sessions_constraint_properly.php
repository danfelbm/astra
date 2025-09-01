<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero eliminar el constraint defectuoso anterior
        try {
            Schema::table('urna_sessions', function (Blueprint $table) {
                $table->dropUnique('unique_active_urna_session');
            });
        } catch (Exception $e) {
            // Si no existe, continúa
        }

        // Crear constraint único real que funcione en MySQL
        // Solo permitir UNA sesión activa por usuario/votación
        // Usaremos SQL directo porque Laravel no maneja bien los índices condicionales
        DB::statement("
            CREATE UNIQUE INDEX unique_active_urna_session 
            ON urna_sessions (votacion_id, usuario_id, (CASE WHEN status = 'active' THEN 'active' ELSE NULL END))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el índice único corregido
        DB::statement("DROP INDEX unique_active_urna_session ON urna_sessions");
        
        // Restaurar el índice defectuoso anterior (para rollback completo)
        Schema::table('urna_sessions', function (Blueprint $table) {
            $table->unique(['votacion_id', 'usuario_id'], 'unique_active_urna_session');
        });
    }
};
