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
        Schema::create('entregable_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entregable_id')->constrained('entregables')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('rol', ['responsable', 'colaborador', 'revisor'])->default('colaborador');
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['entregable_id', 'user_id']);

            // Índices para optimización
            $table->index('entregable_id');
            $table->index('user_id');
            $table->index('rol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entregable_usuario');
    }
};
