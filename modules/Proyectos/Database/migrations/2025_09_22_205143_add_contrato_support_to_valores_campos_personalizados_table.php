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
        Schema::table('valores_campos_personalizados', function (Blueprint $table) {
            // Hacer nullable el proyecto_id para soportar contratos
            $table->unsignedBigInteger('proyecto_id')->nullable()->change();

            // Agregar campo para contratos
            $table->foreignId('contrato_id')->nullable()->after('proyecto_id')
                ->constrained('contratos')->onDelete('cascade');

            // Índice para búsquedas de contratos
            $table->index('contrato_id');

            // Eliminar restricción única antigua
            $table->dropUnique('proyecto_campo_unique');

            // Nuevas restricciones únicas para cada entidad
            $table->unique(['proyecto_id', 'campo_personalizado_id'], 'proyecto_campo_unique');
            $table->unique(['contrato_id', 'campo_personalizado_id'], 'contrato_campo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('valores_campos_personalizados', function (Blueprint $table) {
            // Eliminar restricciones únicas
            $table->dropUnique('contrato_campo_unique');
            $table->dropUnique('proyecto_campo_unique');

            // Eliminar índice y foreign key
            $table->dropForeign(['contrato_id']);
            $table->dropIndex(['contrato_id']);
            $table->dropColumn('contrato_id');

            // Restaurar proyecto_id como no nullable
            $table->unsignedBigInteger('proyecto_id')->nullable(false)->change();

            // Restaurar restricción única original
            $table->unique(['proyecto_id', 'campo_personalizado_id'], 'proyecto_campo_unique');
        });
    }
};