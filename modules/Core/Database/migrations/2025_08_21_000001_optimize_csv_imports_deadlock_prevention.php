<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Optimizaciones para prevenir deadlocks en importaciones CSV grandes
     */
    public function up(): void
    {
        // Añadir índice en la columna id para actualizaciones más rápidas
        // (ya debería existir como primary key, pero asegurémonos)
        if (!$this->indexExists('csv_imports', 'PRIMARY')) {
            Schema::table('csv_imports', function (Blueprint $table) {
                $table->primary('id');
            });
        }
        
        // Añadir índice compuesto para queries de progreso
        if (!$this->indexExists('csv_imports', 'idx_csv_imports_progress')) {
            Schema::table('csv_imports', function (Blueprint $table) {
                $table->index(['id', 'status', 'processed_rows'], 'idx_csv_imports_progress');
            });
        }
        
        // Configurar el motor de tabla para mejor manejo de concurrencia
        // InnoDB con row-level locking es mejor para actualizaciones frecuentes
        DB::statement('ALTER TABLE csv_imports ENGINE=InnoDB ROW_FORMAT=DYNAMIC');
        
        // Configurar auto-commit para transacciones más rápidas
        DB::statement('SET AUTOCOMMIT = 1');
        
        // Crear procedimiento almacenado para actualización atómica de progreso
        // Esto reduce el tiempo de lock y previene deadlocks
        DB::unprepared('
            DROP PROCEDURE IF EXISTS update_csv_import_progress;
            
            CREATE PROCEDURE update_csv_import_progress(
                IN p_id INT,
                IN p_processed INT,
                IN p_successful INT,
                IN p_failed INT,
                IN p_errors JSON
            )
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    -- En caso de error, hacer rollback pero no fallar
                    ROLLBACK;
                END;
                
                START TRANSACTION;
                
                -- Actualización con timeout corto para evitar locks largos
                SET SESSION innodb_lock_wait_timeout = 1;
                
                UPDATE csv_imports 
                SET 
                    processed_rows = p_processed,
                    successful_rows = p_successful,
                    failed_rows = p_failed,
                    errors = p_errors,
                    updated_at = NOW()
                WHERE id = p_id;
                
                COMMIT;
            END
        ');
        
        // Optimizar configuración de la tabla para actualizaciones frecuentes
        DB::statement('OPTIMIZE TABLE csv_imports');
    }
    
    /**
     * Revertir las optimizaciones
     */
    public function down(): void
    {
        // Eliminar procedimiento almacenado
        DB::unprepared('DROP PROCEDURE IF EXISTS update_csv_import_progress');
        
        // Eliminar índice de progreso si existe
        if ($this->indexExists('csv_imports', 'idx_csv_imports_progress')) {
            Schema::table('csv_imports', function (Blueprint $table) {
                $table->dropIndex('idx_csv_imports_progress');
            });
        }
    }
    
    /**
     * Verificar si un índice existe
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};