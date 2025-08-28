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
        Schema::create('user_verification_requests', function (Blueprint $table) {
            $table->id();
            $table->string('documento_identidad');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('verification_code_email', 6)->nullable();
            $table->string('verification_code_whatsapp', 6)->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('whatsapp_verified_at')->nullable();
            $table->enum('status', ['pending', 'verified', 'failed'])->default('pending');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index('documento_identidad');
            $table->index('status');
            $table->index(['documento_identidad', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_verification_requests');
    }
};
