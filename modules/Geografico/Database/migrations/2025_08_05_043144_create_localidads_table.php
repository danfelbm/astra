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
        Schema::create('localidads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipio_id')->constrained('municipios')->onDelete('cascade');
            $table->string('nombre');
            $table->string('codigo', 10)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index(['municipio_id', 'nombre']);
            $table->index('activo');
            $table->unique(['municipio_id', 'nombre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localidads');
    }
};
