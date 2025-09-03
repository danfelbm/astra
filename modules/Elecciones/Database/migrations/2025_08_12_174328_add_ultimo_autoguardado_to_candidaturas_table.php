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
            // Campo para rastrear el último autoguardado
            $table->timestamp('ultimo_autoguardado')->nullable()->after('updated_at');
            
            // Índice para optimizar consultas de autoguardado
            $table->index('ultimo_autoguardado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidaturas', function (Blueprint $table) {
            $table->dropIndex(['ultimo_autoguardado']);
            $table->dropColumn('ultimo_autoguardado');
        });
    }
};
