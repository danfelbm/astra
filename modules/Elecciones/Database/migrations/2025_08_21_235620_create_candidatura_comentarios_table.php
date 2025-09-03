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
        Schema::create('candidatura_comentarios', function (Blueprint $table) {
            $table->id();
            
            // Relación con candidatura
            $table->foreignId('candidatura_id')
                ->constrained('candidaturas')
                ->onDelete('cascade');
            
            // Versión de la candidatura al momento del comentario
            $table->integer('version_candidatura');
            
            // El comentario en sí (HTML permitido)
            $table->text('comentario');
            
            // Tipo de comentario para categorización
            $table->enum('tipo', [
                'general',      // Comentario general/nota administrativa
                'aprobacion',   // Comentario al aprobar
                'rechazo',      // Comentario al rechazar  
                'borrador',     // Comentario al volver a borrador
                'nota_admin'    // Nota interna del admin (no se notifica)
            ])->default('general');
            
            // Si este comentario fue enviado por email al usuario
            $table->boolean('enviado_por_email')->default(false);
            
            // Usuario que creó el comentario
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            
            // Tenant ID para multi-tenancy
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            
            $table->timestamps();
            
            // Índices para consultas frecuentes
            $table->index(['candidatura_id', 'version_candidatura']);
            $table->index(['candidatura_id', 'created_at']);
            $table->index('tipo');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidatura_comentarios');
    }
};