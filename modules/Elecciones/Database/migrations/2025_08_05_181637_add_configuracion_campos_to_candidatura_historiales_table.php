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
        Schema::table('candidatura_historiales', function (Blueprint $table) {
            // Guardar la configuración de campos que estaba activa en el momento del cambio
            $table->json('configuracion_campos_en_momento')->nullable()->after('formulario_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidatura_historiales', function (Blueprint $table) {
            $table->dropColumn('configuracion_campos_en_momento');
        });
    }
};
