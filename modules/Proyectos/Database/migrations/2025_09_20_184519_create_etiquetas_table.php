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
        Schema::create('etiquetas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('slug', 100);
            $table->unsignedBigInteger('categoria_etiqueta_id');
            $table->string('color', 30)->nullable(); // Color opcional, hereda de categoría si es null
            $table->text('descripcion')->nullable();
            $table->integer('usos_count')->default(0); // Contador de usos para estadísticas
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->timestamps();

            // Índices
            $table->unique(['slug', 'tenant_id']);
            $table->index(['categoria_etiqueta_id', 'nombre']);
            $table->index('usos_count');

            // Foreign keys
            $table->foreign('categoria_etiqueta_id')->references('id')->on('categorias_etiquetas')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etiquetas');
    }
};
