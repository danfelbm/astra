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
        Schema::create('candidatura_historiales', function (Blueprint $table) {
            $table->id();
            
            // Relación con candidatura principal
            $table->foreignId('candidatura_id')->constrained('candidaturas')->onDelete('cascade');
            
            // Versión específica de esta entrada del historial
            $table->integer('version');
            
            // Snapshot de los datos en este momento
            $table->json('formulario_data');
            
            // Estado de la candidatura en este momento
            $table->enum('estado_en_momento', ['borrador', 'aprobado', 'rechazado']);
            
            // Comentarios admin que existían en este momento
            $table->text('comentarios_admin_en_momento')->nullable();
            
            // Usuario que realizó el cambio
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Motivo del cambio (opcional)
            $table->text('motivo_cambio')->nullable();
            
            $table->timestamps();
            
            // Índices para consultas frecuentes
            $table->index(['candidatura_id', 'version']);
            $table->index('candidatura_id');
            $table->index('created_by');
            $table->index('created_at');
            
            // Constraint único: una sola entrada por candidatura por versión
            $table->unique(['candidatura_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidatura_historiales');
    }
};
