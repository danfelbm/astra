<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Elecciones Presidenciales',
                'descripcion' => 'Votaciones para elegir presidente y vicepresidente',
                'activa' => true,
            ],
            [
                'nombre' => 'Elecciones Municipales',
                'descripcion' => 'Votaciones para alcaldes y concejos municipales',
                'activa' => true,
            ],
            [
                'nombre' => 'Consultas Populares',
                'descripcion' => 'Consultas y referendos ciudadanos',
                'activa' => true,
            ],
            [
                'nombre' => 'Elecciones Institucionales',
                'descripcion' => 'Votaciones internas de organizaciones',
                'activa' => true,
            ],
            [
                'nombre' => 'Encuestas de Opinión',
                'descripcion' => 'Encuestas y sondeos de opinión pública',
                'activa' => true,
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::firstOrCreate(
                ['nombre' => $categoria['nombre']],
                $categoria
            );
        }

        $this->command->info('Categorías creadas exitosamente.');
    }
}