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
        // Eliminar el constraint condicional defectuoso
        try {
            DB::statement("DROP INDEX unique_active_urna_session ON urna_sessions");
        } catch (Exception $e) {
            // Si no existe, continúa
        }

        // Limpiar sesiones duplicadas antes de crear el constraint absoluto
        // Mantener solo la más reciente para cada usuario/votación
        DB::statement("
            DELETE us1 FROM urna_sessions us1
            INNER JOIN urna_sessions us2
            WHERE us1.votacion_id = us2.votacion_id
                AND us1.usuario_id = us2.usuario_id
                AND us1.id < us2.id
        ");

        // Crear constraint único ABSOLUTO - UN SOLO REGISTRO por usuario/votación
        Schema::table('urna_sessions', function (Blueprint $table) {
            $table->unique(['votacion_id', 'usuario_id'], 'unique_urna_session_per_user');
        });
        
        // Actualizar comentario de la tabla
        DB::statement("
            ALTER TABLE urna_sessions 
            COMMENT = 'Sesiones de urna digital. Solo UNA sesión por usuario/votación (active o voted)'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el constraint único absoluto
        Schema::table('urna_sessions', function (Blueprint $table) {
            $table->dropUnique('unique_urna_session_per_user');
        });

        // Restaurar el constraint condicional anterior (aunque era defectuoso)
        DB::statement("
            CREATE UNIQUE INDEX unique_active_urna_session 
            ON urna_sessions (votacion_id, usuario_id, (CASE WHEN status = 'active' THEN 'active' ELSE NULL END))
        ");
    }
};
