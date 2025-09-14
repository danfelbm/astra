<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración sincroniza todos los permisos del sistema con Spatie Laravel Permission
     */
    public function up(): void
    {
        // Ejecutar el seeder de permisos para sincronizar todos los permisos con Spatie
        Artisan::call('db:seed', [
            '--class' => 'Modules\\Core\\Database\\seeders\\PermissionSeeder',
            '--force' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertir permisos ya que otros datos pueden depender de ellos
        // Solo mostrar advertencia
        echo "ADVERTENCIA: Esta migración no revierte los permisos creados.\n";
        echo "Si necesita eliminar permisos específicos, hágalo manualmente.\n";
    }
};
