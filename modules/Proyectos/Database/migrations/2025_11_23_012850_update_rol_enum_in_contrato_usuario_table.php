<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar valores existentes que son 'participante' a 'testigo'
        // para mantener compatibilidad con el nuevo sistema
        DB::table('contrato_usuario')
            ->where('rol', 'participante')
            ->update(['rol' => 'testigo']);

        // Modificar el ENUM para incluir los nuevos valores
        DB::statement("ALTER TABLE contrato_usuario
            MODIFY COLUMN rol ENUM('testigo', 'revisor', 'aprobador', 'observador')
            DEFAULT 'testigo'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir valores de 'testigo' a 'participante'
        DB::table('contrato_usuario')
            ->where('rol', 'testigo')
            ->update(['rol' => 'participante']);

        // Revertir el ENUM a los valores originales
        DB::statement("ALTER TABLE contrato_usuario
            MODIFY COLUMN rol ENUM('participante', 'observador', 'aprobador')
            DEFAULT 'participante'");
    }
};
