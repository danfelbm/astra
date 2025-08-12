<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Usar SQL directo para manejar la transición correctamente
        DB::statement('ALTER TABLE votos DROP INDEX votos_token_unico_unique');
        DB::statement('ALTER TABLE votos DROP INDEX idx_token');
        DB::statement('ALTER TABLE votos MODIFY token_unico TEXT NOT NULL');
        DB::statement('ALTER TABLE votos ADD UNIQUE KEY votos_token_unico_unique (token_unico(255))');
        DB::statement('ALTER TABLE votos ADD INDEX idx_token (token_unico(255))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votos', function (Blueprint $table) {
            // Revertir a VARCHAR(64) 
            $table->string('token_unico', 64)->change();
        });
    }
};