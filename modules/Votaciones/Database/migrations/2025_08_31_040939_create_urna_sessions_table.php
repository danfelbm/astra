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
        Schema::create('urna_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('votacion_id')->constrained('votaciones')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('opened_at'); // Momento de apertura de urna
            $table->timestamp('closed_at')->nullable(); // Momento de cierre (voto o expiración)
            $table->enum('status', ['active', 'voted', 'expired'])->default('active');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at'); // Momento de expiración
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->timestamps();
            
            // Índices para búsquedas eficientes
            $table->index(['votacion_id', 'usuario_id', 'status']);
            $table->index('expires_at');
            $table->index('status');
            
            // Un usuario solo puede tener una sesión activa por votación
            $table->unique(['votacion_id', 'usuario_id', 'status'], 'unique_active_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urna_sessions');
    }
};