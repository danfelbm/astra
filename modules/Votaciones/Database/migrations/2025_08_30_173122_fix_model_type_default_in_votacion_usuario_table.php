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
        // Primero actualizar todos los registros que tienen el valor incorrecto
        DB::table('votacion_usuario')
            ->where('model_type', 'AppModelsCoreUser')
            ->update(['model_type' => 'Modules\Core\Models\User']);
        
        // Luego cambiar el valor por defecto de la columna
        Schema::table('votacion_usuario', function (Blueprint $table) {
            // Necesitamos modificar la columna para cambiar el default
            $table->string('model_type', 255)
                  ->nullable()
                  ->default('Modules\Core\Models\User')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el valor por defecto (aunque estaba mal, para consistencia)
        Schema::table('votacion_usuario', function (Blueprint $table) {
            $table->string('model_type', 255)
                  ->nullable()
                  ->default('AppModelsCoreUser')
                  ->change();
        });
        
        // Revertir los datos actualizados
        DB::table('votacion_usuario')
            ->where('model_type', 'Modules\Core\Models\User')
            ->whereNull('origen_id') // Solo los que no vienen de sincronización
            ->update(['model_type' => 'AppModelsCoreUser']);
    }
};