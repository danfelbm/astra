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
        Schema::table('contratos', function (Blueprint $table) {
            // Agregar campo para usuario contraparte del sistema
            $table->unsignedBigInteger('contraparte_user_id')->nullable()->after('responsable_id');
            $table->foreign('contraparte_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // Índice para optimización
            $table->index('contraparte_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropForeign(['contraparte_user_id']);
            $table->dropColumn('contraparte_user_id');
        });
    }
};