<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('zoom_registrant_accesses', function (Blueprint $table) {
            // Agregar campo para guardar la URL enmascarada completa que usó el usuario
            $table->text('masked_url')->nullable()->after('ip_address');
        });
        
        // Crear índice parcial usando SQL raw para optimizar búsquedas por URL
        DB::statement('CREATE INDEX zoom_registrant_accesses_masked_url_partial ON zoom_registrant_accesses (masked_url(255))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zoom_registrant_accesses', function (Blueprint $table) {
            // Eliminar índice parcial primero
            DB::statement('DROP INDEX zoom_registrant_accesses_masked_url_partial ON zoom_registrant_accesses');
            
            $table->dropColumn('masked_url');
        });
    }
};
