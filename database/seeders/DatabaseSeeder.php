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
            // \Modules\Geografico\Database\seeders\DivipolSeeder::class, // Requiere archivo divipol.csv
            \Modules\Votaciones\Database\seeders\CategoriaSeeder::class,
            \Modules\Core\Database\seeders\AdminUserSeeder::class,
            \Modules\Core\Database\seeders\VotanteUserSeeder::class,
            \Modules\Elecciones\Database\seeders\CandidaturaConfigSeeder::class,
            \Modules\Elecciones\Database\seeders\PeriodoElectoralSeeder::class,
            \Modules\Elecciones\Database\seeders\CargoSeeder::class,
            \Modules\Elecciones\Database\seeders\ConvocatoriaSeeder::class,
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
