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
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique()->comment('Clave única de configuración (ej: app.logo_display)');
            $table->text('valor')->nullable()->comment('Valor de la configuración (puede ser JSON, string, etc.)');
            $table->enum('tipo', ['string', 'boolean', 'integer', 'float', 'json', 'file'])->default('string')->comment('Tipo de dato para casting automático');
            $table->text('descripcion')->nullable()->comment('Descripción human-readable de la configuración');
            $table->string('categoria')->nullable()->comment('Categoría para agrupar configuraciones (ej: logo, email, sistema)');
            $table->boolean('publico')->default(false)->comment('Si la configuración debe estar disponible en frontend');
            $table->json('validacion')->nullable()->comment('Reglas de validación en formato JSON');
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index('categoria');
            $table->index('publico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};