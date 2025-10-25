<?php

namespace Modules\Proyectos\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Proyectos\Models\Contrato;
use Modules\Proyectos\Models\ObligacionContrato;
use Modules\Core\Models\User;
use Carbon\Carbon;

class ObligacionesSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de obligaciones de contratos.
     */
    public function run(): void
    {
        // Obtener contratos existentes
        $contratos = Contrato::limit(3)->get();

        if ($contratos->isEmpty()) {
            $this->command->info('No hay contratos disponibles para crear obligaciones.');
            return;
        }

        // Obtener usuarios para asignar como responsables
        $usuarios = User::limit(5)->pluck('id')->toArray();

        foreach ($contratos as $contrato) {
            $this->command->info("Creando obligaciones para contrato: {$contrato->nombre}");

            // Crear obligaciones padres
            $obligacionesPadres = [
                [
                    'titulo' => 'Fase de Planificación',
                    'descripcion' => 'Completar toda la planificación del proyecto',
                    'fecha_vencimiento' => Carbon::now()->addDays(30),
                    'prioridad' => 'alta',
                    'hijos' => [
                        [
                            'titulo' => 'Análisis de requerimientos',
                            'descripcion' => 'Documentar todos los requerimientos del proyecto',
                            'fecha_vencimiento' => Carbon::now()->addDays(10),
                            'prioridad' => 'alta'
                        ],
                        [
                            'titulo' => 'Diseño de arquitectura',
                            'descripcion' => 'Crear el diseño arquitectónico del sistema',
                            'fecha_vencimiento' => Carbon::now()->addDays(20),
                            'prioridad' => 'media'
                        ]
                    ]
                ],
                [
                    'titulo' => 'Fase de Desarrollo',
                    'descripcion' => 'Implementación del sistema',
                    'fecha_vencimiento' => Carbon::now()->addDays(60),
                    'prioridad' => 'alta',
                    'hijos' => [
                        [
                            'titulo' => 'Desarrollo del backend',
                            'descripcion' => 'Implementar APIs y lógica de negocio',
                            'fecha_vencimiento' => Carbon::now()->addDays(40),
                            'prioridad' => 'alta'
                        ],
                        [
                            'titulo' => 'Desarrollo del frontend',
                            'descripcion' => 'Crear interfaz de usuario',
                            'fecha_vencimiento' => Carbon::now()->addDays(45),
                            'prioridad' => 'alta'
                        ],
                        [
                            'titulo' => 'Integración de componentes',
                            'descripcion' => 'Integrar todos los módulos del sistema',
                            'fecha_vencimiento' => Carbon::now()->addDays(55),
                            'prioridad' => 'media'
                        ]
                    ]
                ],
                [
                    'titulo' => 'Fase de Testing',
                    'descripcion' => 'Pruebas completas del sistema',
                    'fecha_vencimiento' => Carbon::now()->addDays(75),
                    'prioridad' => 'media',
                    'hijos' => [
                        [
                            'titulo' => 'Pruebas unitarias',
                            'descripcion' => 'Ejecutar y documentar pruebas unitarias',
                            'fecha_vencimiento' => Carbon::now()->addDays(65),
                            'prioridad' => 'media'
                        ],
                        [
                            'titulo' => 'Pruebas de integración',
                            'descripcion' => 'Validar la integración entre componentes',
                            'fecha_vencimiento' => Carbon::now()->addDays(70),
                            'prioridad' => 'media'
                        ]
                    ]
                ],
                [
                    'titulo' => 'Fase de Despliegue',
                    'descripcion' => 'Puesta en producción del sistema',
                    'fecha_vencimiento' => Carbon::now()->addDays(90),
                    'prioridad' => 'alta',
                    'hijos' => []
                ]
            ];

            $orden = 1;

            foreach ($obligacionesPadres as $dataPadre) {
                $hijos = $dataPadre['hijos'];
                unset($dataPadre['hijos']);

                // Crear obligación padre
                $obligacionPadre = ObligacionContrato::create([
                    'contrato_id' => $contrato->id,
                    'parent_id' => null,
                    'titulo' => $dataPadre['titulo'],
                    'descripcion' => $dataPadre['descripcion'],
                    'fecha_vencimiento' => $dataPadre['fecha_vencimiento'],
                    'estado' => 'pendiente',
                    'prioridad' => $dataPadre['prioridad'],
                    'orden' => $orden++,
                    'nivel' => 0,
                    'responsable_id' => $usuarios[array_rand($usuarios)] ?? null,
                    'porcentaje_cumplimiento' => 0,
                    'tenant_id' => $contrato->tenant_id,
                    'created_by' => 1,
                    'updated_by' => 1
                ]);

                // Calcular y actualizar el path
                $obligacionPadre->path = $obligacionPadre->id;
                $obligacionPadre->save();

                // Crear obligaciones hijas
                $ordenHijo = 1;
                foreach ($hijos as $dataHijo) {
                    $obligacionHijo = ObligacionContrato::create([
                        'contrato_id' => $contrato->id,
                        'parent_id' => $obligacionPadre->id,
                        'titulo' => $dataHijo['titulo'],
                        'descripcion' => $dataHijo['descripcion'],
                        'fecha_vencimiento' => $dataHijo['fecha_vencimiento'],
                        'estado' => 'pendiente',
                        'prioridad' => $dataHijo['prioridad'],
                        'orden' => $ordenHijo++,
                        'nivel' => 1,
                        'responsable_id' => $usuarios[array_rand($usuarios)] ?? null,
                        'porcentaje_cumplimiento' => 0,
                        'tenant_id' => $contrato->tenant_id,
                        'created_by' => 1,
                        'updated_by' => 1
                    ]);

                    // Calcular y actualizar el path
                    $obligacionHijo->path = $obligacionPadre->path . '.' . $obligacionHijo->id;
                    $obligacionHijo->save();
                }
            }

            $this->command->info("  ✓ Creadas {$orden} obligaciones principales con sus respectivos hijos");
        }

        $this->command->info('Seeder de obligaciones completado exitosamente.');
    }
}