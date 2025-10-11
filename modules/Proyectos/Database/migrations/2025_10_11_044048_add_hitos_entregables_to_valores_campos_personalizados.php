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
            // Agregar soporte para hitos y entregables
            $table->foreignId('hito_id')->nullable()->after('contrato_id')
                ->constrained('hitos')->onDelete('cascade');
            $table->foreignId('entregable_id')->nullable()->after('hito_id')
                ->constrained('entregables')->onDelete('cascade');

            // Índices para búsquedas
            $table->index('hito_id');
            $table->index('entregable_id');

            // Restricciones únicas para cada entidad
            $table->unique(['hito_id', 'campo_personalizado_id'], 'hito_campo_unique');
            $table->unique(['entregable_id', 'campo_personalizado_id'], 'entregable_campo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('valores_campos_personalizados', function (Blueprint $table) {
            // Eliminar restricciones únicas
            $table->dropUnique('entregable_campo_unique');
            $table->dropUnique('hito_campo_unique');

            // Eliminar índices y foreign keys
            $table->dropForeign(['entregable_id']);
            $table->dropIndex(['entregable_id']);
            $table->dropColumn('entregable_id');

            $table->dropForeign(['hito_id']);
            $table->dropIndex(['hito_id']);
            $table->dropColumn('hito_id');
        });
    }
};
