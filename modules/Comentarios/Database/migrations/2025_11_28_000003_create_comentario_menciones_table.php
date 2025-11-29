<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de menciones (@usuario) en comentarios.
     * Preparada para sistema de notificaciones futuro.
     */
    public function up(): void
    {
        Schema::create('comentario_menciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comentario_id')
                ->constrained('comentarios')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Para sistema de notificaciones futuro
            $table->boolean('notificado')->default(false);

            // Multi-tenancy
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->timestamp('created_at')->nullable();

            // Un usuario solo puede ser mencionado una vez por comentario
            $table->unique(['comentario_id', 'user_id'], 'unique_comentario_mencion');
            $table->index('user_id', 'idx_user');
            $table->index(['user_id', 'notificado'], 'idx_user_notificado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentario_menciones');
    }
};
