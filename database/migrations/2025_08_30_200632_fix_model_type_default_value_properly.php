<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Usar SQL directo para establecer el valor por defecto con las barras invertidas correctas
        DB::statement("ALTER TABLE votacion_usuario ALTER COLUMN model_type SET DEFAULT 'App\\\\Models\\\\Core\\\\User'");
        
        // También actualizar cualquier registro existente que tenga el valor incorrecto
        DB::table('votacion_usuario')
            ->where('model_type', 'AppModelsCoreUser')
            ->update(['model_type' => 'App\\Models\\Core\\User']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al valor anterior (aunque estaba mal)
        DB::statement("ALTER TABLE votacion_usuario ALTER COLUMN model_type SET DEFAULT 'AppModelsCoreUser'");
    }
};