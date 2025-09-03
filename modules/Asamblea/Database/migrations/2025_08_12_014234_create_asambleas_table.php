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
        Schema::create('asambleas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable(); // Se añadirá después en migración específica
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->datetime('fecha_inicio');
            $table->datetime('fecha_fin');
            $table->unsignedBigInteger('territorio_id')->nullable();
            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->unsignedBigInteger('municipio_id')->nullable();
            $table->unsignedBigInteger('localidad_id')->nullable();
            $table->string('lugar')->nullable(); // Dirección física
            $table->integer('quorum_minimo')->nullable();
            $table->enum('tipo', ['ordinaria', 'extraordinaria'])->default('ordinaria');
            $table->enum('estado', ['programada', 'en_curso', 'finalizada', 'cancelada'])->default('programada');
            $table->boolean('activo')->default(true);
            $table->string('acta_url')->nullable(); // Para almacenar el documento del acta
            $table->timestamps();

            // Foreign keys para ubicación geográfica
            $table->foreign('territorio_id')->references('id')->on('territorios')->onDelete('set null');
            $table->foreign('departamento_id')->references('id')->on('departamentos')->onDelete('set null');
            $table->foreign('municipio_id')->references('id')->on('municipios')->onDelete('set null');
            $table->foreign('localidad_id')->references('id')->on('localidades')->onDelete('set null');
            
            // Índices para optimizar consultas
            $table->index(['fecha_inicio', 'fecha_fin']);
            $table->index(['estado', 'activo']);
            $table->index('territorio_id');
            $table->index('departamento_id');
            $table->index('municipio_id');
            $table->index('localidad_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asambleas');
    }
};
