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
        Schema::table('evidencias', function (Blueprint $table) {
            // Agregar nuevos campos para múltiples archivos
            $table->json('archivos_paths')->nullable()->after('archivo_path');
            $table->json('archivos_nombres')->nullable()->after('archivo_nombre');
            $table->unsignedTinyInteger('total_archivos')->default(0)->after('archivos_nombres');

            // Migrar datos existentes de archivo_path a archivos_paths
            // Esto se hará con un comando separado después de la migración
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidencias', function (Blueprint $table) {
            // Eliminar campos de múltiples archivos
            $table->dropColumn(['archivos_paths', 'archivos_nombres', 'total_archivos']);
        });
    }
};
