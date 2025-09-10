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
        Schema::create('campana_metricas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('campana_id')->unique();
            // Métricas generales
            $table->integer('total_destinatarios')->default(0);
            $table->integer('total_enviados')->default(0);
            $table->integer('total_pendientes')->default(0);
            $table->integer('total_fallidos')->default(0);
            // Métricas de email
            $table->integer('emails_enviados')->default(0);
            $table->integer('emails_abiertos')->default(0);
            $table->integer('emails_con_click')->default(0);
            $table->integer('emails_rebotados')->default(0);
            $table->integer('total_clicks')->default(0);
            $table->decimal('tasa_apertura', 5, 2)->default(0);
            $table->decimal('tasa_click', 5, 2)->default(0);
            $table->decimal('tasa_rebote', 5, 2)->default(0);
            // Métricas de WhatsApp
            $table->integer('whatsapp_enviados')->default(0);
            $table->integer('whatsapp_fallidos')->default(0);
            $table->integer('whatsapp_entregados')->default(0);
            // Tiempos
            $table->decimal('tiempo_promedio_apertura', 10, 2)->default(0); // en minutos
            $table->decimal('tiempo_promedio_click', 10, 2)->default(0); // en minutos
            // Metadata
            $table->timestamp('ultima_actualizacion')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['tenant_id', 'campana_id']);
            
            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('campana_id')->references('id')->on('campanas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campana_metricas');
    }
};
