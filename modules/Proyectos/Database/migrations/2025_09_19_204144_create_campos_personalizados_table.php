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
        Schema::create('campos_personalizados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->enum('tipo', ['text', 'number', 'date', 'textarea', 'select', 'checkbox', 'radio', 'file'])->default('text');
            $table->json('opciones')->nullable(); // Para select/radio - almacena las opciones disponibles
            $table->boolean('es_requerido')->default(false);
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->text('descripcion')->nullable(); // Texto de ayuda para el campo
            $table->string('placeholder')->nullable();
            $table->string('validacion')->nullable(); // Reglas de validación adicionales
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            // Índices
            $table->index('slug');
            $table->index('tipo');
            $table->index('activo');
            $table->index('orden');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campos_personalizados');
    }
};
