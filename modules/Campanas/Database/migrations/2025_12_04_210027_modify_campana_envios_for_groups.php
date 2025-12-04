<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     * Modifica campana_envios para soportar envíos a grupos de WhatsApp:
     * - Hace user_id nullable (grupos no tienen usuario asociado)
     * - Agrega 'whatsapp_group' al enum tipo
     */
    public function up(): void
    {
        // Paso 1: Eliminar la foreign key existente
        Schema::table('campana_envios', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Paso 2: Hacer user_id nullable
        Schema::table('campana_envios', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        // Paso 3: Recrear la foreign key que permita nulls
        Schema::table('campana_envios', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // Paso 4: Modificar el enum para incluir 'whatsapp_group'
        DB::statement("ALTER TABLE campana_envios MODIFY COLUMN tipo ENUM('email', 'whatsapp', 'whatsapp_group') NOT NULL");
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        // No se puede revertir si hay registros con user_id null
        // Eliminar registros de grupos primero si existen
        DB::table('campana_envios')
            ->where('tipo', 'whatsapp_group')
            ->delete();

        // Revertir el enum
        DB::statement("ALTER TABLE campana_envios MODIFY COLUMN tipo ENUM('email', 'whatsapp') NOT NULL");

        // Eliminar la foreign key
        Schema::table('campana_envios', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Hacer user_id NOT NULL de nuevo
        Schema::table('campana_envios', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        // Recrear la foreign key original
        Schema::table('campana_envios', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
