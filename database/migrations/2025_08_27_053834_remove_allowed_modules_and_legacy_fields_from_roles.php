<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Eliminar columnas legacy que ya no se usan con Spatie Laravel Permission
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Eliminar campo allowed_modules que ya no se usa
            // Los módulos ahora se derivan de los permisos
            if (Schema::hasColumn('roles', 'allowed_modules')) {
                $table->dropColumn('allowed_modules');
            }
            
            // Eliminar campo legacy_permissions si existe
            // Los permisos ahora se manejan con Spatie
            if (Schema::hasColumn('roles', 'legacy_permissions')) {
                $table->dropColumn('legacy_permissions');
            }
            
            // Eliminar campo permissions original si aún existe
            // (algunas instalaciones pueden aún tenerlo)
            if (Schema::hasColumn('roles', 'permissions')) {
                $table->dropColumn('permissions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Restaurar columnas si se necesita revertir
            if (!Schema::hasColumn('roles', 'allowed_modules')) {
                $table->json('allowed_modules')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('roles', 'legacy_permissions')) {
                $table->json('legacy_permissions')->nullable()->after('allowed_modules');
            }
        });
    }
};
