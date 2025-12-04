<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('whatsapp_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_jid')->unique(); // Ej: "120363295648424210@g.us"
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['grupo', 'comunidad'])->default('grupo');
            $table->string('avatar_url')->nullable();
            $table->unsignedInteger('participantes_count')->default(0);
            $table->string('owner_jid')->nullable();
            $table->boolean('is_announce')->default(false); // Solo anuncios
            $table->boolean('is_restrict')->default(false); // Restringido
            $table->json('metadata')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index('nombre');
            $table->index('tipo');
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_groups');
    }
};
