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
        Schema::table('candidaturas', function (Blueprint $table) {
            // Columna para permitir excepciones en el bloqueo de edición
            // Cuando subsanar = 1 y estado = borrador, permite editar aunque esté bloqueado
            $table->boolean('subsanar')->default(false)->after('version')
                ->comment('Permite editar candidatura en estado borrador aunque esté bloqueado globalmente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidaturas', function (Blueprint $table) {
            $table->dropColumn('subsanar');
        });
    }
};
