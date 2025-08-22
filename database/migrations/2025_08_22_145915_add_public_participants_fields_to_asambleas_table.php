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
        Schema::table('asambleas', function (Blueprint $table) {
            // Campos para configuración de consulta pública de participantes
            $table->boolean('public_participants_enabled')->default(false)
                ->comment('Habilita la consulta pública de participantes sin autenticación');
            
            $table->enum('public_participants_mode', ['list', 'search'])->default('list')
                ->comment('Modo de visualización pública: list (listado con filtros) o search (solo búsqueda)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asambleas', function (Blueprint $table) {
            $table->dropColumn(['public_participants_enabled', 'public_participants_mode']);
        });
    }
};
