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
            // Agregar campo para la ruta de redirección después del login
            $table->string('redirect_after_login')->nullable()->after('is_administrative')
                  ->comment('Ruta a la que se redirige al usuario después del login');
        });

        // Actualizar roles existentes con valores por defecto según su tipo
        DB::table('roles')->where('name', 'super_admin')->update(['redirect_after_login' => 'admin.dashboard']);
        DB::table('roles')->where('name', 'admin')->update(['redirect_after_login' => 'admin.dashboard']);
        DB::table('roles')->where('name', 'manager')->update(['redirect_after_login' => 'admin.dashboard']);
        DB::table('roles')->where('name', 'user')->update(['redirect_after_login' => 'dashboard']);
        DB::table('roles')->where('name', 'end_customer')->update(['redirect_after_login' => 'dashboard']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('redirect_after_login');
        });
    }
};