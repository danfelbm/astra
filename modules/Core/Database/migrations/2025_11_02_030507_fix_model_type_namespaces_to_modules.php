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
     * Corrige el namespace de model_type de App\Models\Core\User
     * a Modules\Core\Models\User en todas las tablas polimórficas
     */
    public function up(): void
    {
        // Actualizar model_has_roles
        DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\Core\\User')
            ->update(['model_type' => 'Modules\\Core\\Models\\User']);

        // Actualizar model_has_permissions
        DB::table('model_has_permissions')
            ->where('model_type', 'App\\Models\\Core\\User')
            ->update(['model_type' => 'Modules\\Core\\Models\\User']);

        // Actualizar segments (si tiene registros con el namespace viejo)
        DB::table('segments')
            ->where('model_type', 'App\\Models\\Core\\User')
            ->update(['model_type' => 'Modules\\Core\\Models\\User']);

        // Actualizar votacion_usuario (si tiene registros con el namespace viejo)
        DB::table('votacion_usuario')
            ->where('model_type', 'App\\Models\\Core\\User')
            ->update(['model_type' => 'Modules\\Core\\Models\\User']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a namespace antiguo
        DB::table('model_has_roles')
            ->where('model_type', 'Modules\\Core\\Models\\User')
            ->update(['model_type' => 'App\\Models\\Core\\User']);

        DB::table('model_has_permissions')
            ->where('model_type', 'Modules\\Core\\Models\\User')
            ->update(['model_type' => 'App\\Models\\Core\\User']);

        DB::table('segments')
            ->where('model_type', 'Modules\\Core\\Models\\User')
            ->update(['model_type' => 'App\\Models\\Core\\User']);

        DB::table('votacion_usuario')
            ->where('model_type', 'Modules\\Core\\Models\\User')
            ->update(['model_type' => 'App\\Models\\Core\\User']);
    }
};
