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
        // Optimizar tabla csv_imports para archivos grandes
        Schema::table('csv_imports', function (Blueprint $table) {
            // Índices para mejorar performance de queries frecuentes
            $table->index(['status', 'created_at'], 'idx_csv_imports_status_date');
            $table->index(['import_type', 'status'], 'idx_csv_imports_type_status');
            $table->index(['created_by', 'status'], 'idx_csv_imports_user_status');
        });

        // Optimizar tabla jobs para queue performance con archivos grandes
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                // Índice compuesto para mejorar rendimiento del queue worker
                if (!Schema::hasIndex('jobs', 'jobs_queue_status_index')) {
                    $table->index(['queue', 'reserved_at'], 'jobs_queue_status_index');
                }
            });
        }

        // Optimizar tabla users para importaciones grandes
        Schema::table('users', function (Blueprint $table) {
            // Mejorar búsquedas por email y documento para detectar duplicados
            if (!$this->hasIndex('users', 'users_email_index')) {
                $table->index(['email'], 'users_email_index');
            }
            if (!$this->hasIndex('users', 'users_documento_index')) {
                $table->index(['documento_identidad'], 'users_documento_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('csv_imports', function (Blueprint $table) {
            $table->dropIndex('idx_csv_imports_status_date');
            $table->dropIndex('idx_csv_imports_type_status');
            $table->dropIndex('idx_csv_imports_user_status');
        });

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                if ($this->hasIndex('jobs', 'jobs_queue_status_index')) {
                    $table->dropIndex('jobs_queue_status_index');
                }
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if ($this->hasIndex('users', 'users_email_index')) {
                $table->dropIndex('users_email_index');
            }
            if ($this->hasIndex('users', 'users_documento_index')) {
                $table->dropIndex('users_documento_index');
            }
        });
    }

    /**
     * Verificar si existe un índice
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }
};