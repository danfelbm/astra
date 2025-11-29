<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     * Agrega el campo aplicar_para para permitir asignar categorías a entidades específicas.
     */
    public function up(): void
    {
        // Verificar si la columna ya existe
        if (!Schema::hasColumn('categorias_etiquetas', 'aplicar_para')) {
            Schema::table('categorias_etiquetas', function (Blueprint $table) {
                // Array JSON con las entidades donde aplica la categoría
                $table->json('aplicar_para')->nullable()->after('activo');
            });
        }

        // Migrar datos existentes: categorías sin aplicar_para → todas las entidades
        DB::table('categorias_etiquetas')
            ->whereNull('aplicar_para')
            ->update(['aplicar_para' => json_encode(['proyectos', 'hitos', 'entregables'])]);
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('categorias_etiquetas', function (Blueprint $table) {
            $table->dropColumn('aplicar_para');
        });
    }
};
