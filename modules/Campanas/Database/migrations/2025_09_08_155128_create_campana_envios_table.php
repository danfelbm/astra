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
        Schema::create('campana_envios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('campana_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('tipo', ['email', 'whatsapp']);
            $table->enum('estado', ['pendiente', 'enviando', 'enviado', 'abierto', 'click', 'fallido'])->default('pendiente');
            $table->string('tracking_id')->unique();
            $table->string('destinatario'); // email o teléfono
            $table->timestamp('fecha_enviado')->nullable();
            $table->timestamp('fecha_abierto')->nullable();
            $table->timestamp('fecha_primer_click')->nullable();
            $table->timestamp('fecha_ultimo_click')->nullable();
            $table->integer('clicks_count')->default(0);
            $table->text('error_mensaje')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['tenant_id', 'campana_id']);
            $table->index(['campana_id', 'estado']);
            $table->index(['campana_id', 'tipo']);
            $table->index('user_id');
            $table->index('tracking_id');
            $table->index('destinatario');
            $table->index('fecha_enviado');
            
            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('campana_id')->references('id')->on('campanas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campana_envios');
    }
};
