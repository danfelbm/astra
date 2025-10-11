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
        // Verificar si la columna ya existe (para evitar errores en producción)
        if (!Schema::hasColumn('campos_personalizados', 'aplicar_para')) {
            Schema::table('campos_personalizados', function (Blueprint $table) {
                // Agregar columna aplicar_para después de validacion
                $table->json('aplicar_para')->nullable()->after('validacion');
            });
        }

        // Migrar datos existentes: campos sin aplicar_para → ['proyectos']
        DB::table('campos_personalizados')
            ->whereNull('aplicar_para')
            ->update(['aplicar_para' => json_encode(['proyectos'])]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campos_personalizados', function (Blueprint $table) {
            $table->dropColumn('aplicar_para');
        });
    }
};
