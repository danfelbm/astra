<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     * Tabla pivot para relación campañas - grupos de WhatsApp
     */
    public function up(): void
    {
        Schema::create('campana_whatsapp_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained('campanas')->onDelete('cascade');
            $table->foreignId('whatsapp_group_id')->constrained('whatsapp_groups')->onDelete('cascade');
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['campana_id', 'whatsapp_group_id'], 'campana_whatsapp_group_unique');
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('campana_whatsapp_groups');
    }
};
