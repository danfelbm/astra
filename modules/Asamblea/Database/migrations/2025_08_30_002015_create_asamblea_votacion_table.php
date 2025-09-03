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
        Schema::create('asamblea_votacion', function (Blueprint $table) {
            $table->foreignId('asamblea_id')->constrained('asambleas')->onDelete('cascade');
            $table->foreignId('votacion_id')->constrained('votaciones')->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->timestamps();
            
            // Llave primaria compuesta
            $table->primary(['asamblea_id', 'votacion_id']);
            
            // Índices para optimización
            $table->index(['asamblea_id', 'tenant_id']);
            $table->index(['votacion_id', 'tenant_id']);
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asamblea_votacion');
    }
};
