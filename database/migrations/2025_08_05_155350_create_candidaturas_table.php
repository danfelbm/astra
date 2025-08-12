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
        Schema::create('candidaturas', function (Blueprint $table) {
            $table->id();
            
            // Relación con usuario propietario del perfil
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Datos del formulario de candidatura (JSON)
            $table->json('formulario_data')->nullable();
            
            // Estado del perfil de candidatura
            $table->enum('estado', ['borrador', 'aprobado', 'rechazado'])->default('borrador');
            
            // Comentarios del administrador para aprobación/rechazo
            $table->text('comentarios_admin')->nullable();
            
            // Admin que aprobó el perfil
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('aprobado_at')->nullable();
            
            // Control de versiones para ediciones
            $table->integer('version')->default(1);
            
            $table->timestamps();
            
            // Índices para consultas frecuentes
            $table->index(['user_id', 'estado']);
            $table->index('estado');
            $table->index('aprobado_por');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidaturas');
    }
};
