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
        Schema::table('campana_envios', function (Blueprint $table) {
            // Contador de todas las aperturas del email
            $table->unsignedInteger('aperturas_count')->default(0)->after('clicks_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campana_envios', function (Blueprint $table) {
            $table->dropColumn('aperturas_count');
        });
    }
};
