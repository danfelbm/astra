<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega campo metadata para almacenar contexto de comentarios.
     * Usado para cambios de estado, acciones y otros metadatos contextuales.
     */
    public function up(): void
    {
        Schema::table('comentarios', function (Blueprint $table) {
            // Campo JSON para almacenar metadata contextual (agnóstico)
            $table->json('metadata')->nullable()->after('total_archivos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comentarios', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
