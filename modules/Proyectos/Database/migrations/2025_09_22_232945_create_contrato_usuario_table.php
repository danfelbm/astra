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
        Schema::create('contrato_usuario', function (Blueprint $table) {
            $table->id();

            // Relación con contrato
            $table->unsignedBigInteger('contrato_id');
            $table->foreign('contrato_id')
                  ->references('id')
                  ->on('contratos')
                  ->onDelete('cascade');

            // Relación con usuario
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Rol del usuario en el contrato
            $table->enum('rol', ['participante', 'observador', 'aprobador'])
                  ->default('participante');

            // Notas o comentarios
            $table->text('notas')->nullable();

            // Timestamps
            $table->timestamps();

            // Índices para optimización
            $table->index('contrato_id');
            $table->index('user_id');
            $table->unique(['contrato_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrato_usuario');
    }
};