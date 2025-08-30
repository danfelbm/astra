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
        Schema::table('votacion_usuario', function (Blueprint $table) {
            // Columnas para rastrear el origen del votante
            $table->unsignedBigInteger('origen_id')->nullable()->after('usuario_id');
            $table->string('model_type', 255)->nullable()->default('App\Models\Core\User')->after('origen_id');
            
            // Índices para optimización de consultas
            $table->index('origen_id');
            $table->index('model_type');
            $table->index(['origen_id', 'model_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votacion_usuario', function (Blueprint $table) {
            $table->dropIndex(['origen_id', 'model_type']);
            $table->dropIndex(['model_type']);
            $table->dropIndex(['origen_id']);
            
            $table->dropColumn(['origen_id', 'model_type']);
        });
    }
};
