<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renombrar campo notas_completado a observaciones_estado
     * para permitir observaciones en cualquier cambio de estado.
     */
    public function up(): void
    {
        Schema::table('entregables', function (Blueprint $table) {
            $table->renameColumn('notas_completado', 'observaciones_estado');
        });
    }

    /**
     * Revertir el cambio de nombre.
     */
    public function down(): void
    {
        Schema::table('entregables', function (Blueprint $table) {
            $table->renameColumn('observaciones_estado', 'notas_completado');
        });
    }
};
