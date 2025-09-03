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
        Schema::table('users', function (Blueprint $table) {
            // Modificar la longitud del campo telefono para soportar formato E.164
            // Formato E.164 puede tener hasta 15 dígitos + el símbolo '+'
            // Ejemplo: +573001234567 (Colombia)
            // Aumentamos a 30 caracteres para dar espacio suficiente
            $table->string('telefono', 30)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Volver al tamaño original
            $table->string('telefono', 20)->nullable()->change();
        });
    }
};