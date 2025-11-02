<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Elimina tablas legacy que quedaron del proceso de migración a Spatie:
     * - roles_old: Copia de la tabla roles antes de migrar a Spatie
     * - permissions_backup: Respaldo temporal de permisos
     */
    public function up(): void
    {
        // Eliminar tabla roles_old (legacy de migración a Spatie)
        Schema::dropIfExists('roles_old');

        // Eliminar tabla permissions_backup (backup temporal)
        Schema::dropIfExists('permissions_backup');
    }

    /**
     * Reverse the migrations.
     *
     * ADVERTENCIA: No se pueden restaurar los datos, solo la estructura vacía
     */
    public function down(): void
    {
        // Nota: No se puede restaurar perfectamente sin los datos originales
        // Esta reversión solo crea tablas vacías para evitar errores de FK

        // No recreamos las tablas porque no tiene sentido sin datos
        // Si necesitas rollback, restaura desde backup de base de datos
    }
};
