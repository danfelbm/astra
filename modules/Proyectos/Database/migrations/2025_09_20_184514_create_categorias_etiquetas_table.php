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
        Schema::create('categorias_etiquetas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('slug', 100);
            $table->string('color', 30)->default('gray'); // Colores de shadcn-vue
            $table->string('icono', 50)->nullable(); // Nombre del icono de lucide-vue-next
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->timestamps();

            // Índices
            $table->unique(['slug', 'tenant_id']);
            $table->index(['activo', 'orden']);

            // Foreign key
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias_etiquetas');
    }
};
