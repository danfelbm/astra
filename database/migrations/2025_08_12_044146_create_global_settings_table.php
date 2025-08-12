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
        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Clave única de configuración global');
            $table->text('value')->nullable()->comment('Valor de la configuración');
            $table->enum('type', ['string', 'boolean', 'integer', 'float', 'json', 'enum'])->default('string')->comment('Tipo de dato para casting');
            $table->text('description')->nullable()->comment('Descripción de la configuración');
            $table->string('category')->nullable()->comment('Categoría para agrupar configuraciones');
            $table->json('options')->nullable()->comment('Opciones adicionales (ej: valores enum permitidos)');
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index('category');
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_settings');
    }
};
