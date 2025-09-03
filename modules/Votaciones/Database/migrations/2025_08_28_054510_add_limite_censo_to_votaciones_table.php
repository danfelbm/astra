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
            // Añadir campo opcional límite del censo para comparaciones externas
            $table->timestamp('limite_censo')->nullable()->after('fecha_publicacion_resultados');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votaciones', function (Blueprint $table) {
            $table->dropColumn('limite_censo');
        });
    }
};
