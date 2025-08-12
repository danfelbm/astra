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
        Schema::table('postulaciones', function (Blueprint $table) {
            // Agregar campo origen para rastrear desde dónde se creó la postulación
            $table->enum('origen', ['convocatoria', 'candidatura'])
                  ->default('convocatoria')
                  ->after('candidatura_id_origen')
                  ->comment('Origen de la postulación: convocatoria (desde página de convocatoria) o candidatura (automática desde candidatura)');
            
            // Agregar índice para mejorar consultas por origen
            $table->index('origen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postulaciones', function (Blueprint $table) {
            $table->dropIndex(['origen']);
            $table->dropColumn('origen');
        });
    }
};