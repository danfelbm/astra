<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Añade el rol 'gestor' al enum de la columna 'rol' en la tabla proyecto_usuario.
     * Los gestores tienen permisos de edición sobre el proyecto.
     */
    public function up(): void
    {
        // Modificar el enum para incluir 'gestor'
        DB::statement("ALTER TABLE proyecto_usuario MODIFY COLUMN rol ENUM('participante', 'supervisor', 'colaborador', 'gestor') NOT NULL DEFAULT 'participante'");
    }

    /**
     * Reverse the migrations.
     *
     * Elimina el rol 'gestor' del enum (revertir cambios).
     */
    public function down(): void
    {
        // Revertir a los valores originales del enum
        DB::statement("ALTER TABLE proyecto_usuario MODIFY COLUMN rol ENUM('participante', 'supervisor', 'colaborador') NOT NULL DEFAULT 'participante'");
    }
};
