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
        Schema::table('votaciones', function (Blueprint $table) {
            $table->timestamp('fecha_publicacion_resultados')->nullable()->after('resultados_publicos');
            $table->index('fecha_publicacion_resultados', 'idx_fecha_publicacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votaciones', function (Blueprint $table) {
            $table->dropIndex('idx_fecha_publicacion');
            $table->dropColumn('fecha_publicacion_resultados');
        });
    }
};
