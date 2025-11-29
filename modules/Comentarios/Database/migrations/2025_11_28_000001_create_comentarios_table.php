<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla principal de comentarios con relaciones polimórficas.
     * Soporta respuestas anidadas ilimitadas (tipo Reddit) y citas.
     */
    public function up(): void
    {
        Schema::create('comentarios', function (Blueprint $table) {
            $table->id();

            // Relación polimórfica - permite asociar comentarios a cualquier modelo
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');

            // Jerarquía de respuestas (ilimitada)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('comentarios')
                ->cascadeOnDelete();
            $table->unsignedInteger('nivel')->default(0); // Profundidad en la jerarquía

            // Contenido
            $table->text('contenido'); // HTML del editor WYSIWYG
            $table->text('contenido_plain')->nullable(); // Texto plano para búsqueda

            // Cita de otro comentario
            $table->foreignId('quoted_comentario_id')
                ->nullable()
                ->constrained('comentarios')
                ->nullOnDelete();

            // Estado de edición
            $table->boolean('es_editado')->default(false);
            $table->timestamp('editado_at')->nullable();

            // Multi-tenancy y auditoría
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices para rendimiento
            $table->index(['commentable_type', 'commentable_id'], 'idx_commentable');
            $table->index('parent_id', 'idx_parent');
            $table->index('tenant_id', 'idx_tenant');
            $table->index('created_by', 'idx_created_by');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentarios');
    }
};
