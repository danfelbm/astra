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
        Schema::create('formularios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            
            // Información básica
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('slug')->unique();
            $table->foreignId('categoria_id')->nullable()->constrained('formulario_categorias')->nullOnDelete();
            
            // Configuración del formulario
            $table->json('configuracion_campos'); // Array de campos del formulario
            $table->json('configuracion_general')->nullable(); // Configuraciones adicionales
            
            // Control de acceso
            $table->enum('tipo_acceso', ['publico', 'autenticado', 'con_permiso'])->default('autenticado');
            $table->boolean('permite_visitantes')->default(false); // Para usuarios no autenticados
            $table->boolean('requiere_captcha')->default(true); // Para visitantes
            
            // Vigencia
            $table->datetime('fecha_inicio')->nullable();
            $table->datetime('fecha_fin')->nullable();
            
            // Límites
            $table->integer('limite_respuestas')->nullable(); // Null = sin límite
            $table->integer('limite_por_usuario')->default(1); // Respuestas por usuario
            
            // Estado
            $table->enum('estado', ['borrador', 'publicado', 'archivado'])->default('borrador');
            $table->boolean('activo')->default(true);
            
            // Notificaciones
            $table->json('emails_notificacion')->nullable(); // Array de emails para notificar nuevas respuestas
            
            // Mensaje de confirmación personalizado
            $table->text('mensaje_confirmacion')->nullable();
            
            // Metadata
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('actualizado_por')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('tenant_id');
            $table->index('slug');
            $table->index(['estado', 'activo']);
            $table->index(['fecha_inicio', 'fecha_fin']);
            $table->index('tipo_acceso');
            $table->unique(['tenant_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formularios');
    }
};
