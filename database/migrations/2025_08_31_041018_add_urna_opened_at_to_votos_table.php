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
        Schema::table('votos', function (Blueprint $table) {
            // Agregar timestamp de apertura de urna
            $table->timestamp('urna_opened_at')->nullable()->after('token_unico');
            
            // Agregar índice para consultas
            $table->index('urna_opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votos', function (Blueprint $table) {
            $table->dropIndex(['urna_opened_at']);
            $table->dropColumn('urna_opened_at');
        });
    }
};