<?php

namespace Modules\Core\Database\Seeders;

use Modules\Core\Services\ConfiguracionService;
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