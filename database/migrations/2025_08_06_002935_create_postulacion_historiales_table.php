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
        Schema::create('postulacion_historiales', function (Blueprint $table) {
            $table->id();
            
            // Relación con postulación
            $table->foreignId('postulacion_id')->constrained('postulaciones')->onDelete('cascade');
            
            // Estados del cambio
            $table->string('estado_anterior');
            $table->string('estado_nuevo');
            
            // Información del cambio
            $table->text('comentarios')->nullable();
            $table->text('motivo_cambio')->nullable();
            
            // Usuario que realizó el cambio
            $table->foreignId('cambiado_por')->constrained('users')->onDelete('cascade');
            $table->timestamp('fecha_cambio');
            
            // Metadatos adicionales (IP, user agent, etc.)
            $table->json('metadatos')->nullable();
            
            // Timestamps del sistema
            $table->timestamps();
            
            // Índices para consultas frecuentes
            $table->index('postulacion_id');
            $table->index('fecha_cambio');
            $table->index(['postulacion_id', 'fecha_cambio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postulacion_historiales');
    }
};
