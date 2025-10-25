<?php

namespace Database\Seeders;

use Modules\Core\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar seeders para usuarios del sistema
        $this->call([
            ConfiguracionesInitialSeeder::class, // Configuraciones iniciales del sistema
            // \Modules\Geografico\Database\Seeders\DivipolSeeder::class, // Requiere archivo divipol.csv
            \Modules\Votaciones\Database\Seeders\CategoriaSeeder::class,
            \Modules\Core\Database\Seeders\AdminUserSeeder::class,
            \Modules\Core\Database\Seeders\VotanteUserSeeder::class,
            \Modules\Elecciones\Database\Seeders\CandidaturaConfigSeeder::class,
            \Modules\Elecciones\Database\Seeders\PeriodoElectoralSeeder::class,
            \Modules\Elecciones\Database\Seeders\CargoSeeder::class,
            \Modules\Elecciones\Database\Seeders\ConvocatoriaSeeder::class,
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
