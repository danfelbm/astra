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
        Schema::create('proyecto_etiqueta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyecto_id');
            $table->unsignedBigInteger('etiqueta_id');
            $table->integer('orden')->default(0); // Para control de visualización
            $table->timestamp('created_at')->useCurrent(); // Para auditoría

            // Índices
            $table->unique(['proyecto_id', 'etiqueta_id']);
            $table->index(['proyecto_id', 'orden']);
            $table->index('etiqueta_id');

            // Foreign keys
            $table->foreign('proyecto_id')->references('id')->on('proyectos')->onDelete('cascade');
            $table->foreign('etiqueta_id')->references('id')->on('etiquetas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyecto_etiqueta');
    }
};
