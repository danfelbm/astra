<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     * Agrega campo whatsapp_mode para definir tipo de audiencia WhatsApp
     */
    public function up(): void
    {
        Schema::table('campanas', function (Blueprint $table) {
            // Modo de WhatsApp: individual (contactos), grupos, o mixto
            $table->enum('whatsapp_mode', ['individual', 'grupos', 'mixto'])
                ->default('individual')
                ->after('tipo');
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('campanas', function (Blueprint $table) {
            $table->dropColumn('whatsapp_mode');
        });
    }
};
