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
        Schema::create('convocatorias', function (Blueprint $table) {
            $table->id();
            
            // Metadatos básicos de la convocatoria
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->datetime('fecha_apertura');
            $table->datetime('fecha_cierre');
            
            // Referencias a otros módulos
            $table->foreignId('cargo_id')->constrained('cargos')->onDelete('restrict');
            $table->foreignId('periodo_electoral_id')->constrained('periodos_electorales')->onDelete('restrict');
            
            // Restricciones geográficas (todas opcionales)
            $table->unsignedBigInteger('territorio_id')->nullable();
            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->unsignedBigInteger('municipio_id')->nullable();
            $table->unsignedBigInteger('localidad_id')->nullable();
            
            // Configuración del formulario de postulación
            $table->json('formulario_postulacion')->nullable();
            
            // Estado y control
            $table->enum('estado', ['borrador', 'activa', 'cerrada'])->default('borrador');
            $table->boolean('activo')->default(true);
            
            $table->timestamps();
            
            // Índices para optimizar consultas frecuentes
            $table->index(['fecha_apertura', 'fecha_cierre'], 'idx_convocatorias_fechas');
            $table->index(['estado', 'activo'], 'idx_convocatorias_estado');
            $table->index(['cargo_id', 'periodo_electoral_id'], 'idx_convocatorias_referencias');
            $table->index(['territorio_id', 'departamento_id', 'municipio_id', 'localidad_id'], 'idx_convocatorias_geografia');
            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convocatorias');
    }
};
