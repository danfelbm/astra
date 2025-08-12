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
        Schema::create('asamblea_usuario', function (Blueprint $table) {
            $table->foreignId('asamblea_id')->constrained('asambleas')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('tenant_id')->nullable(); // Se añadirá después en migración específica
            $table->enum('tipo_participacion', ['asistente', 'moderador', 'secretario'])->default('asistente');
            $table->boolean('asistio')->default(false);
            $table->datetime('hora_registro')->nullable();
            $table->timestamps();
            
            // Clave primaria compuesta
            $table->primary(['asamblea_id', 'usuario_id']);
            
            // Índice para optimizar consultas por tipo de participación
            $table->index('tipo_participacion');
            $table->index('asistio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asamblea_usuario');
    }
};
