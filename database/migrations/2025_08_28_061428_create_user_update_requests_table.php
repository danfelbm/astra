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
        Schema::create('user_update_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('new_email')->nullable();
            $table->string('new_telefono')->nullable();
            $table->json('documentos_soporte')->nullable(); // Array de rutas de archivos
            $table->string('current_email'); // Email actual para referencia
            $table->string('current_telefono')->nullable(); // Teléfono actual
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            // Índices para búsquedas y filtros
            $table->index('user_id');
            $table->index('status');
            $table->index('admin_id');
            $table->index('tenant_id');
            $table->index(['status', 'tenant_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_update_requests');
    }
};
