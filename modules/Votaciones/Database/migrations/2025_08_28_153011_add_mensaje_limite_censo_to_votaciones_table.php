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
            // Agregar campo para mensaje personalizado cuando usuario excede límite de censo
            $table->text('mensaje_limite_censo')->nullable()->after('limite_censo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votaciones', function (Blueprint $table) {
            $table->dropColumn('mensaje_limite_censo');
        });
    }
};
