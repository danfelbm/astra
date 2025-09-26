<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Repositories\HitoRepository;
use Modules\Proyectos\Services\ProyectoNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HitoService
{
    public function __construct(
        private HitoRepository $repository,
        private ProyectoNotificationService $notificationService,
        private EntregableService $entregableService
    ) {}

    /**
     * Crea un nuevo hito con sus entregables iniciales.
     */
    public function create(array $data): array
    {
        DB::beginTransaction();
        try {
            // Separar entregables si vienen en los datos
            $entregables = $data['entregables'] ?? [];
            unset($data['entregables']);

            // Crear el hito
            $hito = $this->repository->create($data);

            // Crear entregables iniciales si se proporcionaron
            if (!empty($entregables)) {
                foreach ($entregables as $index => $entregableData) {
                    $entregableData['hito_id'] = $hito->id;
                    $entregableData['orden'] = $index + 1;
                    $this->entregableService->create($entregableData);
                }
            }

            // Notificar si hay un responsable asignado
            if ($hito->responsable_id) {
                $this->notificarAsignacion($hito);
            }

            DB::commit();

            return [
                'success' => true,
                'hito' => $hito->fresh(['entregables']),
                'message' => 'Hito creado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear hito: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al crear el hito: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza un hito existente.
     */
    public function update(Hito $hito, array $data): array
    {
        DB::beginTransaction();
        try {
            // Guardar el responsable anterior para notificación
            $responsableAnterior = $hito->responsable_id;
            $estadoAnterior = $hito->estado;

            // Separar entregables si vienen en los datos
            unset($data['entregables']); // Los entregables se gestionan por separado

            // Actualizar el hito
            $this->repository->update($hito, $data);

            // Notificar cambio de responsable si aplica
            if ($responsableAnterior != $hito->responsable_id && $hito->responsable_id) {
                $this->notificarAsignacion($hito);
            }

            // Notificar cambio de estado si aplica
            if ($estadoAnterior != $hito->estado) {
                $this->notificarCambioEstado($hito);
            }

            DB::commit();

            return [
                'success' => true,
                'hito' => $hito->fresh(['entregables']),
                'message' => 'Hito actualizado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar hito: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al actualizar el hito: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina un hito con todos sus entregables.
     */
    public function delete(Hito $hito): array
    {
        DB::beginTransaction();
        try {
            // Los entregables se eliminan automáticamente por la cascada en la BD
            $resultado = $this->repository->delete($hito);

            DB::commit();

            return [
                'success' => $resultado,
                'message' => $resultado ? 'Hito eliminado exitosamente' : 'Error al eliminar el hito'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar hito: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al eliminar el hito: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Duplica un hito con sus entregables.
     */
    public function duplicar(Hito $hito, array $datosNuevos = []): array
    {
        DB::beginTransaction();
        try {
            $nuevoHito = $this->repository->duplicar($hito, $datosNuevos);

            DB::commit();

            return [
                'success' => true,
                'hito' => $nuevoHito,
                'message' => 'Hito duplicado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al duplicar hito: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al duplicar el hito: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reordena hitos dentro de un proyecto.
     */
    public function reordenar(int $proyectoId, array $ordenIds): array
    {
        try {
            $this->repository->reordenar($proyectoId, $ordenIds);

            return [
                'success' => true,
                'message' => 'Hitos reordenados exitosamente'
            ];
        } catch (\Exception $e) {
            Log::error('Error al reordenar hitos: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al reordenar los hitos'
            ];
        }
    }

    /**
     * Cambia el estado de un hito.
     */
    public function cambiarEstado(Hito $hito, string $nuevoEstado): array
    {
        try {
            $estadoAnterior = $hito->estado;

            $hito->estado = $nuevoEstado;
            $hito->updated_by = auth()->id();

            // Si se marca como completado, marcar todos los entregables como completados
            if ($nuevoEstado === 'completado') {
                foreach ($hito->entregables()->whereNotIn('estado', ['completado', 'cancelado'])->get() as $entregable) {
                    $entregable->marcarComoCompletado(auth()->id(), 'Completado automáticamente al completar el hito');
                }
                $hito->porcentaje_completado = 100;
            }

            // Si se cancela, cancelar todos los entregables pendientes
            if ($nuevoEstado === 'cancelado') {
                $hito->entregables()->where('estado', 'pendiente')->update(['estado' => 'cancelado']);
            }

            $hito->save();

            // Notificar cambio de estado
            if ($estadoAnterior != $nuevoEstado) {
                $this->notificarCambioEstado($hito);
            }

            return [
                'success' => true,
                'message' => 'Estado actualizado exitosamente'
            ];
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del hito: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al cambiar el estado del hito'
            ];
        }
    }

    /**
     * Asigna un responsable al hito.
     */
    public function asignarResponsable(Hito $hito, int $responsableId): array
    {
        try {
            $responsableAnterior = $hito->responsable_id;

            $hito->responsable_id = $responsableId;
            $hito->updated_by = auth()->id();
            $hito->save();

            // Notificar al nuevo responsable
            if ($responsableAnterior != $responsableId) {
                $this->notificarAsignacion($hito);
            }

            return [
                'success' => true,
                'message' => 'Responsable asignado exitosamente'
            ];
        } catch (\Exception $e) {
            Log::error('Error al asignar responsable: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al asignar responsable'
            ];
        }
    }

    /**
     * Obtiene estadísticas de hitos para un proyecto.
     */
    public function obtenerEstadisticas(int $proyectoId): array
    {
        return $this->repository->getEstadisticas($proyectoId);
    }

    /**
     * Obtiene hitos con alertas (vencidos o próximos a vencer).
     */
    public function obtenerHitosConAlertas(int $proyectoId = null): array
    {
        $hitos = $this->repository->getHitosConAlertas($proyectoId);

        return [
            'vencidos' => $hitos->filter(fn($h) => $h->esta_vencido),
            'proximos_vencer' => $hitos->filter(fn($h) => $h->esta_proximo_vencer),
            'total_alertas' => $hitos->count()
        ];
    }

    /**
     * Crea hitos predefinidos para un tipo de proyecto.
     */
    public function crearHitosPredefinidos(Proyecto $proyecto, string $tipoProyecto): array
    {
        $plantillas = $this->obtenerPlantillaHitos($tipoProyecto);
        $hitosCreados = [];

        DB::beginTransaction();
        try {
            foreach ($plantillas as $index => $plantilla) {
                $hito = $this->repository->create([
                    'proyecto_id' => $proyecto->id,
                    'nombre' => $plantilla['nombre'],
                    'descripcion' => $plantilla['descripcion'] ?? null,
                    'orden' => $index + 1,
                    'estado' => 'pendiente',
                    'responsable_id' => $proyecto->responsable_id
                ]);

                // Crear entregables predefinidos si existen
                if (isset($plantilla['entregables'])) {
                    foreach ($plantilla['entregables'] as $entIndex => $entregable) {
                        $this->entregableService->create([
                            'hito_id' => $hito->id,
                            'nombre' => $entregable['nombre'],
                            'descripcion' => $entregable['descripcion'] ?? null,
                            'orden' => $entIndex + 1,
                            'estado' => 'pendiente',
                            'prioridad' => $entregable['prioridad'] ?? 'media'
                        ]);
                    }
                }

                $hitosCreados[] = $hito;
            }

            DB::commit();

            return [
                'success' => true,
                'hitos' => $hitosCreados,
                'message' => 'Hitos predefinidos creados exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear hitos predefinidos: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al crear hitos predefinidos'
            ];
        }
    }

    /**
     * Obtiene plantilla de hitos según el tipo de proyecto.
     */
    private function obtenerPlantillaHitos(string $tipoProyecto): array
    {
        $plantillas = [
            'documental' => [
                [
                    'nombre' => 'Pre-producción',
                    'descripcion' => 'Investigación y preparación del proyecto',
                    'entregables' => [
                        ['nombre' => 'Investigación y documentación', 'prioridad' => 'alta'],
                        ['nombre' => 'Guión técnico', 'prioridad' => 'alta'],
                        ['nombre' => 'Plan de rodaje', 'prioridad' => 'media'],
                        ['nombre' => 'Presupuesto detallado', 'prioridad' => 'alta']
                    ]
                ],
                [
                    'nombre' => 'Producción',
                    'descripcion' => 'Grabación y captura de material',
                    'entregables' => [
                        ['nombre' => 'Grabación de entrevistas', 'prioridad' => 'alta'],
                        ['nombre' => 'Grabación de B-Roll', 'prioridad' => 'media'],
                        ['nombre' => 'Registro de audio', 'prioridad' => 'alta'],
                        ['nombre' => 'Respaldo de material', 'prioridad' => 'alta']
                    ]
                ],
                [
                    'nombre' => 'Post-producción',
                    'descripcion' => 'Edición y finalización del proyecto',
                    'entregables' => [
                        ['nombre' => 'Edición rough cut', 'prioridad' => 'alta'],
                        ['nombre' => 'Color grading', 'prioridad' => 'media'],
                        ['nombre' => 'Mezcla de audio', 'prioridad' => 'alta'],
                        ['nombre' => 'Master final', 'prioridad' => 'alta']
                    ]
                ]
            ],
            'software' => [
                [
                    'nombre' => 'Análisis y diseño',
                    'descripcion' => 'Definición de requerimientos y arquitectura',
                    'entregables' => [
                        ['nombre' => 'Documento de requerimientos', 'prioridad' => 'alta'],
                        ['nombre' => 'Diseño de arquitectura', 'prioridad' => 'alta'],
                        ['nombre' => 'Mockups UI/UX', 'prioridad' => 'media']
                    ]
                ],
                [
                    'nombre' => 'Desarrollo',
                    'descripcion' => 'Implementación del software',
                    'entregables' => [
                        ['nombre' => 'Backend API', 'prioridad' => 'alta'],
                        ['nombre' => 'Frontend', 'prioridad' => 'alta'],
                        ['nombre' => 'Integración de servicios', 'prioridad' => 'media']
                    ]
                ],
                [
                    'nombre' => 'Testing y despliegue',
                    'descripcion' => 'Pruebas y puesta en producción',
                    'entregables' => [
                        ['nombre' => 'Pruebas unitarias', 'prioridad' => 'alta'],
                        ['nombre' => 'Pruebas de integración', 'prioridad' => 'alta'],
                        ['nombre' => 'Despliegue en producción', 'prioridad' => 'alta']
                    ]
                ]
            ],
            'generico' => [
                [
                    'nombre' => 'Inicio',
                    'descripcion' => 'Fase inicial del proyecto',
                    'entregables' => [
                        ['nombre' => 'Definición de alcance', 'prioridad' => 'alta'],
                        ['nombre' => 'Plan de trabajo', 'prioridad' => 'alta']
                    ]
                ],
                [
                    'nombre' => 'Desarrollo',
                    'descripcion' => 'Fase de ejecución del proyecto',
                    'entregables' => [
                        ['nombre' => 'Entregable principal', 'prioridad' => 'alta'],
                        ['nombre' => 'Documentación', 'prioridad' => 'media']
                    ]
                ],
                [
                    'nombre' => 'Cierre',
                    'descripcion' => 'Fase de finalización del proyecto',
                    'entregables' => [
                        ['nombre' => 'Entrega final', 'prioridad' => 'alta'],
                        ['nombre' => 'Informe de cierre', 'prioridad' => 'media']
                    ]
                ]
            ]
        ];

        return $plantillas[$tipoProyecto] ?? $plantillas['generico'];
    }

    /**
     * Notifica la asignación de un hito a un responsable.
     */
    private function notificarAsignacion(Hito $hito): void
    {
        // Aquí se puede implementar la lógica de notificación
        // Por ahora solo un log
        Log::info('Hito asignado', [
            'hito_id' => $hito->id,
            'responsable_id' => $hito->responsable_id
        ]);
    }

    /**
     * Notifica el cambio de estado de un hito.
     */
    private function notificarCambioEstado(Hito $hito): void
    {
        // Aquí se puede implementar la lógica de notificación
        // Por ahora solo un log
        Log::info('Estado de hito cambiado', [
            'hito_id' => $hito->id,
            'estado' => $hito->estado
        ]);
    }
}