<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar las migraciones.
     */
    public function up(): void
    {
        Schema::create('entregable_etiqueta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entregable_id')->constrained('entregables')->onDelete('cascade');
            $table->foreignId('etiqueta_id')->constrained('etiquetas')->onDelete('cascade');
            $table->integer('orden')->default(0);
            $table->timestamp('created_at')->useCurrent();

            // Índices y constraints
            $table->unique(['entregable_id', 'etiqueta_id']);
            $table->index(['entregable_id', 'orden']);
        });
    }

    /**
     * Revertir las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('entregable_etiqueta');
    }
};
