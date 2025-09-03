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
        // 1. Índice compuesto para optimizar la verificación de votos duplicados
        // Este índice hace más eficiente la query: WHERE votacion_id = ? AND usuario_id = ?
        Schema::table('votos', function (Blueprint $table) {
            // Primero eliminar el índice único existente si existe
            try {
                $table->dropUnique('unique_voto_usuario');
            } catch (\Exception $e) {
                // El índice podría no existir, continuar
            }
            
            // Crear índice compuesto optimizado
            // votacion_id primero porque es más selectivo (menos votaciones que usuarios)
            $table->index(['votacion_id', 'usuario_id'], 'idx_votacion_usuario_optimized');
            
            // Recrear el constraint único con mejor nombre
            $table->unique(['votacion_id', 'usuario_id'], 'uniq_votacion_usuario');
            
            // Índice para queries de resultados por fecha
            $table->index(['votacion_id', 'created_at'], 'idx_votacion_created');
        });
        
        // 2. Índices para optimizar las queries de votaciones activas
        Schema::table('votaciones', function (Blueprint $table) {
            // Índice compuesto para queries frecuentes de votaciones activas
            // WHERE estado = 'activa' AND fecha_inicio <= NOW() AND fecha_fin >= NOW()
            $table->index(['estado', 'fecha_inicio', 'fecha_fin'], 'idx_votaciones_active');
            
            // Índice para filtrado por categoría
            if (!$this->indexExists('votaciones', 'votaciones_categoria_id_foreign')) {
                $table->index('categoria_id', 'idx_votaciones_categoria');
            }
        });
        
        // 3. Índices para la tabla pivote votacion_usuario
        Schema::table('votacion_usuario', function (Blueprint $table) {
            // Índice inverso para queries desde el usuario
            // Para optimizar: $user->votaciones()
            $table->index(['usuario_id', 'votacion_id'], 'idx_usuario_votacion');
            
            // Índice en created_at para ordenamiento temporal
            $table->index('created_at', 'idx_votacion_usuario_created');
        });
        
        // 4. Índices para optimizar queries geográficas de usuarios
        Schema::table('users', function (Blueprint $table) {
            // Índice compuesto para filtros geográficos frecuentes
            $table->index(['territorio_id', 'departamento_id', 'municipio_id'], 'idx_users_geographic');
            
            // Índice para búsqueda por email (ya debe existir pero por si acaso)
            if (!$this->indexExists('users', 'users_email_unique')) {
                $table->index('email', 'idx_users_email');
            }
        });
        
        // 5. Índice virtual para búsquedas en campos JSON (MySQL 5.7+)
        // Esto optimiza las queries con JSON_EXTRACT en resultados
        if (DB::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION) >= '5.7') {
            // Crear índice virtual para el campo respuestas más consultado
            // Por ejemplo, si frecuentemente se busca por una pregunta específica
            DB::statement("
                ALTER TABLE votos 
                ADD INDEX idx_votos_respuestas_virtual (
                    (CAST(JSON_EXTRACT(respuestas, '$[*]') AS CHAR(255) ARRAY))
                ) COMMENT 'Virtual index for JSON respuestas field'
            ");
        }
        
        // 6. Optimizar tabla de jobs para el ProcessVoteJob
        Schema::table('jobs', function (Blueprint $table) {
            // Índice para jobs pendientes por cola
            $table->index(['queue', 'reserved_at'], 'idx_jobs_queue_reserved');
            
            // Índice para limpieza de jobs antiguos
            if (!$this->indexExists('jobs', 'jobs_available_at_index')) {
                $table->index('available_at', 'idx_jobs_available');
            }
        });
        
        // 7. Actualizar estadísticas de las tablas para el optimizador
        DB::statement('ANALYZE TABLE votos');
        DB::statement('ANALYZE TABLE votaciones');
        DB::statement('ANALYZE TABLE votacion_usuario');
        DB::statement('ANALYZE TABLE users');
        
        // Log de éxito
        \Log::info('Índices de optimización para votaciones creados exitosamente');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices en orden inverso
        Schema::table('votos', function (Blueprint $table) {
            $table->dropIndex('idx_votacion_usuario_optimized');
            $table->dropUnique('uniq_votacion_usuario');
            $table->dropIndex('idx_votacion_created');
            
            // Restaurar el índice único original
            $table->unique(['votacion_id', 'usuario_id'], 'unique_voto_usuario');
        });
        
        Schema::table('votaciones', function (Blueprint $table) {
            $table->dropIndex('idx_votaciones_active');
            $table->dropIndex('idx_votaciones_categoria');
        });
        
        Schema::table('votacion_usuario', function (Blueprint $table) {
            $table->dropIndex('idx_usuario_votacion');
            $table->dropIndex('idx_votacion_usuario_created');
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_geographic');
            if ($this->indexExists('users', 'idx_users_email')) {
                $table->dropIndex('idx_users_email');
            }
        });
        
        // Eliminar índice virtual JSON si existe
        try {
            DB::statement("DROP INDEX idx_votos_respuestas_virtual ON votos");
        } catch (\Exception $e) {
            // El índice podría no existir
        }
        
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('idx_jobs_queue_reserved');
            if ($this->indexExists('jobs', 'idx_jobs_available')) {
                $table->dropIndex('idx_jobs_available');
            }
        });
    }
    
    /**
     * Verificar si un índice existe.
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEXES FROM {$table} WHERE Key_name = ?", [$index]);
        return count($indexes) > 0;
    }
};