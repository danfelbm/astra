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
        // Primero, limpiar cualquier sesión activa duplicada que pueda existir
        // Mantener solo la más reciente para cada usuario/votación
        DB::statement("
            DELETE us1 FROM urna_sessions us1
            INNER JOIN urna_sessions us2
            WHERE us1.votacion_id = us2.votacion_id
                AND us1.usuario_id = us2.usuario_id
                AND us1.status = 'active'
                AND us2.status = 'active'
                AND us1.id < us2.id
        ");
        
        Schema::table('urna_sessions', function (Blueprint $table) {
            // Crear índice único condicional para prevenir múltiples sesiones activas
            // Este índice único solo aplica cuando status = 'active'
            // Permitiendo múltiples sesiones con status 'voted' o 'expired'
            $table->unique(
                ['votacion_id', 'usuario_id'], 
                'unique_active_urna_session'
            )->where('status', '=', 'active');
        });
        
        // Agregar comentario explicativo
        DB::statement("
            ALTER TABLE urna_sessions 
            COMMENT = 'Sesiones de urna digital para votaciones. Solo puede existir una sesión activa por usuario/votación'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('urna_sessions', function (Blueprint $table) {
            // Eliminar el índice único condicional
            $table->dropUnique('unique_active_urna_session');
        });
        
        // Restaurar comentario original
        DB::statement("
            ALTER TABLE urna_sessions 
            COMMENT = ''
        ");
    }
};
