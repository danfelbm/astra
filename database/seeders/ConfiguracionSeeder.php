<?php

namespace Database\Seeders;

use App\Services\Core\ConfiguracionService;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inicializar configuraciones por defecto del sistema
        ConfiguracionService::inicializarConfiguracionesPorDefecto();
    }
}