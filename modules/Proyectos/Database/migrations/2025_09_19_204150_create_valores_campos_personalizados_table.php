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
        Schema::create('valores_campos_personalizados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->foreignId('campo_personalizado_id')->constrained('campos_personalizados')->onDelete('cascade');
            $table->text('valor')->nullable(); // El valor guardado para este campo
            $table->timestamps();

            // Índices
            $table->index('proyecto_id');
            $table->index('campo_personalizado_id');
            $table->unique(['proyecto_id', 'campo_personalizado_id'], 'proyecto_campo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valores_campos_personalizados');
    }
};
