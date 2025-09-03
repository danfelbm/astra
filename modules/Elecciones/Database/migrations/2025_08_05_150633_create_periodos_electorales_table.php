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
        Schema::create('periodos_electorales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->datetime('fecha_inicio');
            $table->datetime('fecha_fin');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Índices para optimizar consultas por fechas y estado
            $table->index(['fecha_inicio', 'fecha_fin']);
            $table->index(['activo', 'fecha_inicio']);
            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos_electorales');
    }
};
