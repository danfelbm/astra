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
            // Añadir tipo de documento después de documento_identidad
            $table->enum('tipo_documento', ['TI', 'CC', 'CE', 'PA'])
                  ->default('CC')
                  ->after('documento_identidad')
                  ->comment('TI: Tarjeta de identidad, CC: Cédula de ciudadanía, CE: Cédula de extranjería, PA: Pasaporte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tipo_documento');
        });
    }
};