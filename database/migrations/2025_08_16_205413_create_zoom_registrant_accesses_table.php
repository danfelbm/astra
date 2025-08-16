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
        Schema::create('zoom_registrant_accesses', function (Blueprint $table) {
            $table->id();
            
            // Relación con zoom_registrants
            $table->foreignId('zoom_registrant_id')->constrained('zoom_registrants')->onDelete('cascade');
            
            // Campos de tracking
            $table->unsignedInteger('access_count')->default(0);
            $table->timestamp('first_accessed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            
            // Información de acceso
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            
            $table->timestamps();
            
            // Índices para optimización
            $table->index('zoom_registrant_id');
            $table->index('access_count');
            $table->index(['zoom_registrant_id', 'access_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_registrant_accesses');
    }
};
