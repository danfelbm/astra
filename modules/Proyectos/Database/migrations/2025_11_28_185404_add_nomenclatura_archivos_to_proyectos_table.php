<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega campo para configurar la nomenclatura de archivos de evidencias.
     */
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            // Campo para definir el patrón de nombres de archivos
            // Ejemplo: "{proyecto_id}-{hito_id}-{entregable_id}_{fecha:Ymd}_{original}"
            $table->string('nomenclatura_archivos')->nullable()->after('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn('nomenclatura_archivos');
        });
    }
};
