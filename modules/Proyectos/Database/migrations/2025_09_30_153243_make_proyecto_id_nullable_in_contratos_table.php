<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Hace proyecto_id nullable para permitir borradores sin proyecto asignado.
     * Esto es necesario para el autosave durante la creación de contratos.
     */
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            // Primero eliminar la foreign key existente
            $table->dropForeign(['proyecto_id']);

            // Modificar la columna para que sea nullable
            $table->unsignedBigInteger('proyecto_id')->nullable()->change();

            // Recrear la foreign key con la columna nullable
            $table->foreign('proyecto_id')
                ->references('id')
                ->on('proyectos')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            // Eliminar la foreign key
            $table->dropForeign(['proyecto_id']);

            // Revertir a no nullable (pero solo si no hay registros con NULL)
            $table->unsignedBigInteger('proyecto_id')->nullable(false)->change();

            // Recrear la foreign key
            $table->foreign('proyecto_id')
                ->references('id')
                ->on('proyectos')
                ->onDelete('cascade');
        });
    }
};
