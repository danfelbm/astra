<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        // Tabla principal de evidencias
        Schema::create('evidencias', function (Blueprint $table) {
            $table->id();

            // Relación con obligación (requerido)
            $table->foreignId('obligacion_id')
                ->constrained('obligaciones_contrato')
                ->onDelete('cascade');

            // Usuario que sube la evidencia
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Tipo de evidencia
            $table->enum('tipo_evidencia', ['imagen', 'video', 'audio', 'documento']);

            // Ruta del archivo
            $table->string('archivo_path');
            $table->string('archivo_nombre')->nullable(); // Nombre original del archivo

            // Descripción de la evidencia
            $table->text('descripcion')->nullable();

            // Metadata del archivo (json)
            $table->json('metadata')->nullable(); // mime_type, size, duration, etc.

            // Estado de la evidencia
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');

            // Observaciones del administrador
            $table->text('observaciones_admin')->nullable();

            // Fecha de revisión
            $table->timestamp('revisado_at')->nullable();
            $table->foreignId('revisado_por')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Multi-tenancy
            $table->foreignId('tenant_id')->nullable();

            // Auditoría
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Índices
            $table->index(['obligacion_id', 'estado']);
            $table->index(['user_id', 'created_at']);
            $table->index('tenant_id');
        });

        // Tabla pivot para relacionar evidencias con entregables
        Schema::create('evidencia_entregable', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evidencia_id')
                ->constrained('evidencias')
                ->onDelete('cascade');

            $table->foreignId('entregable_id')
                ->constrained('entregables')
                ->onDelete('cascade');

            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['evidencia_id', 'entregable_id']);
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evidencia_entregable');
        Schema::dropIfExists('evidencias');
    }
};