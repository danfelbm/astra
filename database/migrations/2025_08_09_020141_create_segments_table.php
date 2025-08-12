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
        Schema::create('segments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('model_type')->default('App\\Models\\User'); // Modelo al que aplica
            $table->json('filters'); // Configuración de filtros avanzados
            $table->boolean('is_dynamic')->default(true); // Si se recalcula dinámicamente
            $table->integer('cache_duration')->default(300); // Duración del cache en segundos
            $table->json('metadata')->nullable(); // contacts_count, last_calculated_at, etc.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade');
            
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            // Índices
            $table->index('tenant_id');
            $table->index('model_type');
            $table->index('is_dynamic');
        });
        
        // No crear segmentos de ejemplo para evitar problemas de foreign key
        // Los segmentos se crearán desde la interfaz o mediante seeders
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segments');
    }
};