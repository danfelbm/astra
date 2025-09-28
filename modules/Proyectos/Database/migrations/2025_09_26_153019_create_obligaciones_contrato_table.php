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
        Schema::create('obligaciones_contrato', function (Blueprint $table) {
            $table->id();

            // Relación con contrato
            $table->unsignedBigInteger('contrato_id');
            $table->foreign('contrato_id')->references('id')->on('contratos')->onDelete('cascade');

            // Estructura jerárquica (padre-hijo)
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('obligaciones_contrato')->onDelete('cascade');

            // Información básica
            $table->string('titulo');
            $table->text('descripcion')->nullable();

            // Campos deprecados - mantenidos por compatibilidad
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'en_progreso', 'cumplida', 'vencida', 'cancelada'])->nullable()->default('pendiente');
            $table->enum('prioridad', ['baja', 'media', 'alta'])->nullable()->default('media');

            // Jerarquía y ordenamiento
            $table->integer('orden')->default(1);
            $table->integer('nivel')->default(0); // Profundidad en el árbol (0 = raíz)
            $table->string('path', 500)->nullable(); // Path materializado para queries eficientes

            // Campos deprecados de responsables y cumplimiento - mantenidos por compatibilidad
            $table->unsignedBigInteger('responsable_id')->nullable();
            $table->foreign('responsable_id')->references('id')->on('users')->onDelete('set null');
            $table->integer('porcentaje_cumplimiento')->nullable()->default(0);
            $table->text('notas_cumplimiento')->nullable();
            $table->timestamp('cumplido_at')->nullable();
            $table->unsignedBigInteger('cumplido_por')->nullable();
            $table->foreign('cumplido_por')->references('id')->on('users')->onDelete('set null');

            // Archivos adjuntos
            $table->json('archivos_adjuntos')->nullable(); // Array de rutas de archivos

            // Multi-tenancy
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            // Índices para optimización
            $table->index('contrato_id');
            $table->index('parent_id');
            $table->index('estado');
            $table->index('fecha_vencimiento');
            $table->index('responsable_id');
            $table->index('path');
            $table->index('tenant_id');
            $table->index(['contrato_id', 'orden']);
            $table->index(['parent_id', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obligaciones_contrato');
    }
};
