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
        Schema::table('candidatura_historiales', function (Blueprint $table) {
            // Eliminar la restricción única actual
            $table->dropUnique(['candidatura_id', 'version']);
        });
        
        // Agregar estado 'pendiente' al enum
        DB::statement("ALTER TABLE candidatura_historiales MODIFY COLUMN estado_en_momento ENUM('borrador', 'pendiente', 'aprobado', 'rechazado')");
        
        Schema::table('candidatura_historiales', function (Blueprint $table) {
            // Crear nueva restricción única que incluye el estado
            // Esto permite múltiples entradas por versión si el estado es diferente
            $table->unique(['candidatura_id', 'version', 'estado_en_momento'], 'historiales_candidatura_version_estado_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidatura_historiales', function (Blueprint $table) {
            // Eliminar la nueva restricción única
            $table->dropUnique('historiales_candidatura_version_estado_unique');
        });
        
        // Revertir enum al estado original
        DB::statement("ALTER TABLE candidatura_historiales MODIFY COLUMN estado_en_momento ENUM('borrador', 'aprobado', 'rechazado')");
        
        Schema::table('candidatura_historiales', function (Blueprint $table) {
            // Restaurar la restricción única original
            $table->unique(['candidatura_id', 'version']);
        });
    }
};
