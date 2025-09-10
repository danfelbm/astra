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
        Schema::create('campanas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['email', 'whatsapp', 'ambos']);
            $table->enum('estado', ['borrador', 'programada', 'enviando', 'completada', 'pausada', 'cancelada'])->default('borrador');
            $table->unsignedBigInteger('segment_id')->nullable();
            $table->unsignedBigInteger('plantilla_email_id')->nullable();
            $table->unsignedBigInteger('plantilla_whatsapp_id')->nullable();
            $table->timestamp('fecha_programada')->nullable();
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->json('configuracion')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['tenant_id', 'estado']);
            $table->index(['tenant_id', 'fecha_programada']);
            $table->index('segment_id');
            $table->index('plantilla_email_id');
            $table->index('plantilla_whatsapp_id');
            $table->index('created_by');
            
            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('segment_id')->references('id')->on('segments')->onDelete('set null');
            $table->foreign('plantilla_email_id')->references('id')->on('plantilla_emails')->onDelete('set null');
            $table->foreign('plantilla_whatsapp_id')->references('id')->on('plantilla_whats_apps')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campanas');
    }
};
