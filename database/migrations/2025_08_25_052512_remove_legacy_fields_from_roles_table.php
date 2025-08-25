<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Eliminar campo legacy_permissions (anteriormente permissions)
            if (Schema::hasColumn('roles', 'legacy_permissions')) {
                $table->dropColumn('legacy_permissions');
            }
            
            // Eliminar campo allowed_modules si existe
            if (Schema::hasColumn('roles', 'allowed_modules')) {
                $table->dropColumn('allowed_modules');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Restaurar campo legacy_permissions
            $table->json('legacy_permissions')->nullable()->after('description');
            
            // Restaurar campo allowed_modules
            $table->json('allowed_modules')->nullable()->after('legacy_permissions');
        });
    }
};