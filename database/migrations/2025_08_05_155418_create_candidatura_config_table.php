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
        Schema::create('candidatura_config', function (Blueprint $table) {
            $table->id();
            
            // Configuración de campos del formulario de candidatura (JSON)
            $table->json('campos')->nullable();
            
            // Si esta configuración está activa
            $table->boolean('activo')->default(true);
            
            // Admin que creó/modificó la configuración
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            
            // Control de versiones de la configuración
            $table->integer('version')->default(1);
            
            $table->timestamps();
            
            // Índices para consultas frecuentes
            $table->index('activo');
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidatura_config');
    }
};
