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
        Schema::table('evidencias', function (Blueprint $table) {
            // Campo JSON para mapear cada archivo a su tipo
            // Estructura: { "path/to/file1.jpg": "imagen", "path/to/file2.pdf": "documento", ... }
            $table->json('tipos_archivos')->nullable()->after('metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidencias', function (Blueprint $table) {
            $table->dropColumn('tipos_archivos');
        });
    }
};
