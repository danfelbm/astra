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
        Schema::create('proyecto_usuario', function (Blueprint $table) {
            $table->id();

            // Relación con proyecto
            $table->unsignedBigInteger('proyecto_id');
            $table->foreign('proyecto_id')
                  ->references('id')
                  ->on('proyectos')
                  ->onDelete('cascade');

            // Relación con usuario
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Rol del usuario en el proyecto
            $table->enum('rol', ['participante', 'supervisor', 'colaborador'])
                  ->default('participante');

            // Timestamps
            $table->timestamps();

            // Índices para optimización
            $table->index('proyecto_id');
            $table->index('user_id');
            $table->unique(['proyecto_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyecto_usuario');
    }
};