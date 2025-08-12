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
        Schema::create('role_segments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('segment_id');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('role_id')
                  ->references('id')
                  ->on('roles')
                  ->onDelete('cascade');
            
            $table->foreign('segment_id')
                  ->references('id')
                  ->on('segments')
                  ->onDelete('cascade');
            
            // Índice único para evitar duplicados
            $table->unique(['role_id', 'segment_id']);
            
            // Índices adicionales
            $table->index('role_id');
            $table->index('segment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_segments');
    }
};