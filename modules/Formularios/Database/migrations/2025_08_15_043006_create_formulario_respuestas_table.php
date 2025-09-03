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
        Schema::create('formulario_respuestas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            
            // Relaciones
            $table->foreignId('formulario_id')->constrained('formularios')->onDelete('cascade');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Datos del respondiente (para visitantes)
            $table->string('nombre_visitante')->nullable();
            $table->string('email_visitante')->nullable();
            $table->string('telefono_visitante')->nullable();
            $table->string('documento_visitante')->nullable();
            
            // Respuestas
            $table->json('respuestas'); // Objeto con las respuestas del formulario
            $table->json('metadata')->nullable(); // Metadata adicional (IP, user agent, etc)
            
            // Estado
            $table->enum('estado', ['borrador', 'enviado'])->default('enviado');
            $table->string('codigo_confirmacion')->unique()->nullable(); // Código único de confirmación
            
            // Timestamps
            $table->datetime('iniciado_en')->nullable(); // Cuándo empezó a llenar
            $table->datetime('enviado_en')->nullable(); // Cuándo lo envió
            $table->timestamps();
            
            // Índices
            $table->index('tenant_id');
            $table->index(['formulario_id', 'usuario_id']);
            $table->index(['formulario_id', 'estado']);
            $table->index('codigo_confirmacion');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formulario_respuestas');
    }
};
