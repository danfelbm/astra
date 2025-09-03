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
        Schema::create('zoom_registrants', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('asamblea_id')->constrained('asambleas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Datos de Zoom
            $table->string('zoom_registrant_id'); // ID único de Zoom
            $table->text('zoom_join_url'); // URL para unirse
            $table->string('zoom_participant_pin_code')->nullable(); // PIN si es requerido
            $table->timestamp('zoom_start_time')->nullable(); // Hora de inicio de la reunión
            $table->string('zoom_topic')->nullable(); // Tema de la reunión
            $table->json('zoom_occurrences')->nullable(); // Información de ocurrencias
            $table->timestamp('registered_at'); // Cuándo se registró
            
            $table->timestamps();
            
            // Índices para optimización
            $table->index(['asamblea_id', 'user_id']);
            $table->unique(['asamblea_id', 'user_id']); // Un registro por usuario/asamblea
            $table->index('zoom_registrant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_registrants');
    }
};
