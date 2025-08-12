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
        Schema::create('postulaciones', function (Blueprint $table) {
            $table->id();
            
            // Relaciones principales
            $table->foreignId('convocatoria_id')->constrained('convocatorias')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Datos del formulario de postulación (respuestas dinámicas)
            $table->json('formulario_data');
            
            // Snapshot completo de candidatura aprobada (si se vincula)
            $table->json('candidatura_snapshot')->nullable();
            
            // Referencia débil a candidatura original (para auditoría)
            $table->unsignedBigInteger('candidatura_id_origen')->nullable();
            
            // Estado de la postulación
            $table->enum('estado', ['borrador', 'enviada', 'en_revision', 'aceptada', 'rechazada'])
                  ->default('borrador');
            
            // Metadatos
            $table->datetime('fecha_postulacion')->nullable(); // Cuando cambia de borrador a enviada
            $table->text('comentarios_admin')->nullable(); // Feedback del admin
            $table->foreignId('revisado_por')->nullable()->constrained('users');
            $table->datetime('revisado_at')->nullable();
            
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['convocatoria_id', 'estado']);
            $table->index(['user_id', 'estado']);
            $table->index('fecha_postulacion');
            
            // Restricción: una postulación por usuario por convocatoria
            $table->unique(['convocatoria_id', 'user_id'], 'postulaciones_convocatoria_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postulaciones');
    }
};
