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
        // Agregar campo is_administrative a la tabla roles
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_administrative')->default(true)->after('is_system')
                ->comment('Indica si el rol tiene acceso a áreas administrativas (/admin)');
        });

        // Actualizar los roles existentes según su naturaleza
        // Roles administrativos (por defecto true)
        DB::table('roles')
            ->whereIn('name', ['super_admin', 'admin', 'manager'])
            ->update(['is_administrative' => true]);
        
        // Roles NO administrativos - usuario final
        DB::table('roles')
            ->whereIn('name', ['user', 'end_customer'])
            ->update(['is_administrative' => false]);
        
        // Roles personalizados existentes se asumen como administrativos
        DB::table('roles')
            ->whereNotIn('name', ['super_admin', 'admin', 'manager', 'user', 'end_customer'])
            ->update(['is_administrative' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_administrative');
        });
    }
};
