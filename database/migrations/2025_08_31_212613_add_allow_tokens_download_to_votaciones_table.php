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
            $table->boolean('allow_tokens_download')->default(false)->after('resultados_publicos')
                ->comment('Permite la descarga de tokens en formato CSV');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votaciones', function (Blueprint $table) {
            $table->dropColumn('allow_tokens_download');
        });
    }
};
