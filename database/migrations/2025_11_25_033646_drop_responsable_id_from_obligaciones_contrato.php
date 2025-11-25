<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Elimina la columna responsable_id que está deprecada.
     * El responsable se obtiene ahora desde el contrato asociado.
     */
    public function up(): void
    {
        Schema::table('obligaciones_contrato', function (Blueprint $table) {
            // Eliminar foreign key primero si existe
            $table->dropForeign(['responsable_id']);
            // Eliminar índice
            $table->dropIndex(['responsable_id']);
            // Eliminar columna
            $table->dropColumn('responsable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obligaciones_contrato', function (Blueprint $table) {
            $table->unsignedBigInteger('responsable_id')->nullable()->after('path');
            $table->foreign('responsable_id')->references('id')->on('users')->onDelete('set null');
            $table->index('responsable_id');
        });
    }
};
