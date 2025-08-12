<?php

namespace Database\Seeders;

use App\Models\Cargo;
use Illuminate\Database\Seeder;

class CargoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Iniciando seeder de Cargos de Elección Popular...');

        // Verificar si ya existen cargos para evitar duplicados
        if (Cargo::count() > 0) {
            $this->command->warn('Ya existen cargos en el sistema. Verificando si necesitamos agregar nuevos...');
        }

        $cargosCreados = 0;
        $cargosExistentes = 0;

        // Estructura de cargos a crear
        $estructuraCargos = [
            // Congreso de la República
            [
                'categoria' => 'Congreso de la República',
                'descripcion' => 'Corporaciones del poder legislativo nacional',
                'cargos' => [
                    [
                        'nombre' => 'Cámara de Representantes',
                        'descripcion' => 'Representantes a la Cámara del Congreso de la República de Colombia',
                    ],
                    [
                        'nombre' => 'Senado de la República',
                        'descripcion' => 'Senadores del Congreso de la República de Colombia',
                    ],
                ]
            ],
            
            // Corporaciones Territoriales
            [
                'categoria' => 'Corporaciones Territoriales',
                'descripcion' => 'Corporaciones de elección popular a nivel territorial',
                'cargos' => [
                    [
                        'nombre' => 'Asamblea Departamental',
                        'descripcion' => 'Diputados a las Asambleas Departamentales',
                    ],
                    [
                        'nombre' => 'Concejo Municipal',
                        'descripcion' => 'Concejales municipales y distritales',
                    ],
                    [
                        'nombre' => 'Junta Administradora Local',
                        'descripcion' => 'Ediles de Juntas Administradoras Locales (JAL)',
                    ],
                ]
            ],
            
            // Poder Ejecutivo
            [
                'categoria' => 'Poder Ejecutivo',
                'descripcion' => 'Cargos ejecutivos de elección popular',
                'cargos' => [
                    [
                        'nombre' => 'Presidencia de la República',
                        'descripcion' => 'Presidente y Vicepresidente de la República de Colombia',
                    ],
                    [
                        'nombre' => 'Gobernación',
                        'descripcion' => 'Gobernadores departamentales',
                    ],
                    [
                        'nombre' => 'Alcaldía',
                        'descripcion' => 'Alcaldes municipales y distritales',
                    ],
                ]
            ],
        ];

        foreach ($estructuraCargos as $grupo) {
            // Crear o encontrar la categoría padre
            $categoria = Cargo::where('nombre', $grupo['categoria'])
                ->where('es_cargo', false)
                ->first();

            if (!$categoria) {
                $categoria = Cargo::create([
                    'parent_id' => null,
                    'nombre' => $grupo['categoria'],
                    'descripcion' => $grupo['descripcion'],
                    'es_cargo' => false, // Es una categoría
                    'activo' => true,
                ]);
                $cargosCreados++;
                $this->command->info("✅ Categoría creada: {$grupo['categoria']}");
            } else {
                $cargosExistentes++;
                $this->command->info("ℹ️ Categoría ya existe: {$grupo['categoria']}");
            }

            // Crear los cargos hijos
            foreach ($grupo['cargos'] as $cargoData) {
                $cargoExistente = Cargo::where('nombre', $cargoData['nombre'])
                    ->where('parent_id', $categoria->id)
                    ->where('es_cargo', true)
                    ->first();

                if (!$cargoExistente) {
                    $cargo = Cargo::create([
                        'parent_id' => $categoria->id,
                        'nombre' => $cargoData['nombre'],
                        'descripcion' => $cargoData['descripcion'],
                        'es_cargo' => true, // Es un cargo real
                        'activo' => true,
                    ]);
                    $cargosCreados++;
                    $this->command->info("   ├── Cargo creado: {$cargoData['nombre']}");
                } else {
                    $cargosExistentes++;
                    $this->command->info("   ├── Cargo ya existe: {$cargoData['nombre']}");
                }
            }
        }

        $this->command->info("\n📊 Resumen de la operación:");
        $this->command->info("   - Nuevos cargos/categorías creados: {$cargosCreados}");
        $this->command->info("   - Cargos/categorías ya existentes: {$cargosExistentes}");
        $this->command->info("   - Total en el sistema: " . Cargo::count());
        $this->command->info("   - Cargos disponibles para convocatorias: " . Cargo::soloCargos()->count());
        $this->command->info("   - Categorías organizacionales: " . Cargo::soloCategories()->count());
        
        $this->command->info("\n🏛️ Estructura jerárquica creada:");
        $this->mostrarEstructuraJerarquica();
    }

    /**
     * Mostrar la estructura jerárquica de cargos creada
     */
    private function mostrarEstructuraJerarquica(): void
    {
        $raices = Cargo::raices()->orderBy('nombre')->get();

        foreach ($raices as $raiz) {
            $tipoIcon = $raiz->es_cargo ? '🏛️' : '📂';
            $this->command->info("   {$tipoIcon} {$raiz->nombre}");
            
            $hijos = $raiz->children()->orderBy('nombre')->get();
            foreach ($hijos as $hijo) {
                $tipoIconHijo = $hijo->es_cargo ? '🏛️' : '📂';
                $this->command->info("      └── {$tipoIconHijo} {$hijo->nombre}");
            }
        }
    }
}