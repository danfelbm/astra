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
        Schema::table('hitos', function (Blueprint $table) {
            // Agregar soporte para jerarquía de hitos
            $table->foreignId('parent_id')->nullable()->after('proyecto_id')
                ->constrained('hitos')->onDelete('cascade');
            $table->integer('nivel')->default(0)->after('parent_id');
            $table->string('ruta')->nullable()->after('nivel');

            // Índices para optimización de consultas jerárquicas
            $table->index('parent_id');
            $table->index('nivel');
            $table->index(['proyecto_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hitos', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['proyecto_id', 'parent_id']);
            $table->dropIndex(['nivel']);
            $table->dropIndex(['parent_id']);

            // Eliminar foreign key y columnas
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'nivel', 'ruta']);
        });
    }
};
