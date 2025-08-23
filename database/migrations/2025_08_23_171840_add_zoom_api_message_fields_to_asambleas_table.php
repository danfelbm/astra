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
        Schema::table('asambleas', function (Blueprint $table) {
            $table->boolean('zoom_api_message_enabled')
                  ->default(false)
                  ->after('zoom_static_message')
                  ->comment('Habilitar mensaje estático en modo API');
                  
            $table->text('zoom_api_message')
                  ->nullable()
                  ->after('zoom_api_message_enabled')
                  ->comment('Mensaje a mostrar encima del enlace de Zoom en modo API');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asambleas', function (Blueprint $table) {
            $table->dropColumn(['zoom_api_message_enabled', 'zoom_api_message']);
        });
    }
};