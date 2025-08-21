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
        Schema::table('csv_imports', function (Blueprint $table) {
            // Añadir campo para asamblea_id después de votacion_id
            $table->foreignId('asamblea_id')
                ->nullable()
                ->after('votacion_id')
                ->constrained('asambleas')
                ->onDelete('cascade');
            
            // Añadir índice para optimizar consultas
            $table->index('asamblea_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('csv_imports', function (Blueprint $table) {
            $table->dropForeign(['asamblea_id']);
            $table->dropColumn('asamblea_id');
        });
    }
};
