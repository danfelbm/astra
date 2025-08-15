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
            // Campos para integración con Zoom
            $table->boolean('zoom_enabled')->default(false)->after('acta_url');
            $table->string('zoom_meeting_id')->nullable()->after('zoom_enabled');
            $table->string('zoom_meeting_password')->nullable()->after('zoom_meeting_id');
            $table->enum('zoom_meeting_type', ['instant', 'scheduled', 'recurring'])->default('scheduled')->after('zoom_meeting_password');
            $table->json('zoom_settings')->nullable()->after('zoom_meeting_type'); // Configuraciones adicionales
            $table->timestamp('zoom_created_at')->nullable()->after('zoom_settings'); // Cuándo se creó la reunión en Zoom
            $table->text('zoom_join_url')->nullable()->after('zoom_created_at'); // URL de unión directa
            $table->text('zoom_start_url')->nullable()->after('zoom_join_url'); // URL para iniciar (hosts)
            
            // Índices para optimizar consultas
            $table->index(['zoom_enabled', 'zoom_meeting_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asambleas', function (Blueprint $table) {
            // Eliminar índices primero
            $table->dropIndex(['zoom_enabled', 'zoom_meeting_id']);
            
            // Eliminar campos de Zoom
            $table->dropColumn([
                'zoom_enabled',
                'zoom_meeting_id',
                'zoom_meeting_password',
                'zoom_meeting_type',
                'zoom_settings',
                'zoom_created_at',
                'zoom_join_url',
                'zoom_start_url'
            ]);
        });
    }
};
