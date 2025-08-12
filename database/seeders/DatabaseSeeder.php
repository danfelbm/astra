<?php

namespace Database\Seeders;

use App\Models\User;
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
            DivipolSeeder::class, // Poblar territorios, departamentos, municipios y localidades
            CategoriaSeeder::class,
            AdminUserSeeder::class,
            VotanteUserSeeder::class,
            CandidaturaConfigSeeder::class, // Configuración inicial de candidaturas
            PeriodoElectoralSeeder::class, // Periodo electoral para convocatorias
            CargoSeeder::class, // Cargos de elección popular
            ConvocatoriaSeeder::class, // Convocatorias para Cámara de Representantes
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
