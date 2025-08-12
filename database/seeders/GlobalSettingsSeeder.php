<?php

namespace Database\Seeders;

use App\Enums\LoginType;
use App\Services\GlobalSettingsService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GlobalSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inicializar configuraciones globales por defecto
        GlobalSettingsService::initializeDefaults();
        
        // Por defecto, establecer login por email
        GlobalSettingsService::setLoginType(LoginType::EMAIL);
        
        $this->command->info('Configuraciones globales inicializadas.');
        $this->command->info('Tipo de login configurado: ' . LoginType::EMAIL->label());
        $this->command->info('');
        $this->command->info('Para cambiar a login por documento, ejecute:');
        $this->command->info('php artisan tinker');
        $this->command->info('>>> \App\Services\GlobalSettingsService::setLoginType(\App\Enums\LoginType::DOCUMENTO);');
    }
}
