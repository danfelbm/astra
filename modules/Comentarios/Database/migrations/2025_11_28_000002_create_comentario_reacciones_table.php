<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de reacciones (emojis) para comentarios.
     * Cada usuario puede agregar múltiples emojis diferentes al mismo comentario.
     */
    public function up(): void
    {
        Schema::create('comentario_reacciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comentario_id')
                ->constrained('comentarios')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Emoji: thumbs_up, thumbs_down, heart, laugh, clap, fire, check, eyes
            $table->string('emoji', 50);

            // Multi-tenancy
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->timestamp('created_at')->nullable();

            // Un usuario solo puede poner un emoji específico una vez por comentario
            $table->unique(['comentario_id', 'user_id', 'emoji'], 'unique_user_emoji');
            $table->index('comentario_id', 'idx_comentario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentario_reacciones');
    }
};
