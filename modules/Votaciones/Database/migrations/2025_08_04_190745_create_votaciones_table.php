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
        Schema::create('votaciones', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->json('formulario_config');
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin');
            $table->enum('estado', ['borrador', 'activa', 'finalizada'])->default('borrador');
            $table->boolean('resultados_publicos')->default(false);
            $table->timestamps();
            
            $table->index(['estado', 'fecha_inicio', 'fecha_fin'], 'idx_estado_fechas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votaciones');
    }
};
