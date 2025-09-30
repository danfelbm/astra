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
        Schema::table('contratos', function (Blueprint $table) {
            // Campos para soporte de múltiples archivos
            $table->json('archivos_paths')->nullable()->after('archivo_pdf');
            $table->json('archivos_nombres')->nullable()->after('archivos_paths');
            $table->json('tipos_archivos')->nullable()->after('archivos_nombres');
            $table->integer('total_archivos')->default(0)->after('tipos_archivos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            // Eliminar campos de múltiples archivos
            $table->dropColumn(['archivos_paths', 'archivos_nombres', 'tipos_archivos', 'total_archivos']);
        });
    }
};
