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
        // Agregar estado 'pendiente' al enum existente
        DB::statement("ALTER TABLE candidaturas MODIFY COLUMN estado ENUM('borrador', 'pendiente', 'aprobado', 'rechazado') DEFAULT 'borrador'");
        
        // También actualizar la tabla de historial si existe
        DB::statement("ALTER TABLE candidatura_historiales MODIFY COLUMN estado_en_momento ENUM('borrador', 'pendiente', 'aprobado', 'rechazado')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver al enum original (remover 'pendiente')
        DB::statement("ALTER TABLE candidaturas MODIFY COLUMN estado ENUM('borrador', 'aprobado', 'rechazado') DEFAULT 'borrador'");
        DB::statement("ALTER TABLE candidatura_historiales MODIFY COLUMN estado_en_momento ENUM('borrador', 'aprobado', 'rechazado')");
    }
};
