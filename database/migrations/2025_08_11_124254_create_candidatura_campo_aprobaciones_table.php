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
        Schema::create('candidatura_campo_aprobaciones', function (Blueprint $table) {
            $table->id();
            
            // Relación con candidatura
            $table->foreignId('candidatura_id')
                ->constrained('candidaturas')
                ->onDelete('cascade');
            
            // Identificador del campo (del formulario dinámico)
            $table->string('campo_id');
            
            // Estado de aprobación
            $table->boolean('aprobado')->default(false);
            
            // Usuario que aprobó/rechazó
            $table->foreignId('aprobado_por')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            
            // Timestamp de aprobación
            $table->timestamp('aprobado_at')->nullable();
            
            // Comentario del administrador (opcional)
            $table->text('comentario')->nullable();
            
            // Versión de la candidatura cuando se aprobó
            $table->integer('version_candidatura');
            
            // Valor del campo en el momento de la aprobación (para historial)
            $table->json('valor_aprobado')->nullable();
            
            // Tenant ID para multi-tenancy
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->onDelete('cascade');
            
            $table->timestamps();
            
            // Índice único para evitar duplicados
            $table->unique(['candidatura_id', 'campo_id', 'version_candidatura'], 'unique_candidatura_campo_version');
            
            // Índices para búsquedas frecuentes
            $table->index(['candidatura_id', 'aprobado']);
            $table->index('campo_id');
            $table->index('aprobado_por');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidatura_campo_aprobaciones');
    }
};