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
        Schema::table('etiquetas', function (Blueprint $table) {
            // Agregar columnas para jerarquía
            $table->unsignedBigInteger('parent_id')->nullable()->after('categoria_etiqueta_id');
            $table->integer('nivel')->default(0)->after('parent_id');
            $table->string('ruta', 500)->nullable()->after('nivel');

            // Índices para optimizar consultas jerárquicas
            $table->index('parent_id');
            $table->index('nivel');
            $table->index('ruta');

            // Foreign key self-referencing con SET NULL on delete
            // para evitar eliminar hijos cuando se elimina el padre
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('etiquetas')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etiquetas', function (Blueprint $table) {
            // Eliminar foreign key primero
            $table->dropForeign(['parent_id']);

            // Eliminar índices
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['nivel']);
            $table->dropIndex(['ruta']);

            // Eliminar columnas
            $table->dropColumn('parent_id');
            $table->dropColumn('nivel');
            $table->dropColumn('ruta');
        });
    }
};
