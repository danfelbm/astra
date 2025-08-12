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
        Schema::create('votos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('votacion_id')->constrained('votaciones');
            $table->foreignId('usuario_id')->constrained('users');
            $table->string('token_unico', 64)->unique();
            $table->json('respuestas');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->unique(['votacion_id', 'usuario_id'], 'unique_voto_usuario');
            $table->index('token_unico', 'idx_token');
            $table->index('votacion_id', 'idx_votacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votos');
    }
};
