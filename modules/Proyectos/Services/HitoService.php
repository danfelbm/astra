<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Repositories\HitoRepository;
use Modules\Proyectos\Repositories\CampoPersonalizadoRepository;
use Modules\Proyectos\Services\ProyectoNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HitoService
{
    public function __construct(
        private HitoRepository $repository,
        private CampoPersonalizadoRepository $campoPersonalizadoRepository,
        private ProyectoNotificationService $notificationService,
        private EntregableService $entregableService
    ) {}

    /**
     * Crea un nuevo hito con sus entregables iniciales y campos personalizados.
     */
    public function create(array $data): array
    {
        DB::beginTransaction();
        try {
            // Separar datos relacionados
            $entregables = $data['entregables'] ?? [];
            $camposPersonalizados = $data['campos_personalizados'] ?? [];
            $etiquetas = $data['etiquetas'] ?? [];
            unset($data['entregables'], $data['campos_personalizados'], $data['etiquetas']);

            // Validar campos personalizados requeridos
            $this->validarCamposPersonalizadosRequeridos($camposPersonalizados, 'hitos');

            // Crear el hito
            $hito = $this->repository->create($data);

            // Guardar campos personalizados si existen
            if (!empty($camposPersonalizados)) {
                $hito->saveCamposPersonalizados($camposPersonalizados);
            }

            // Sincronizar etiquetas si existen
            if (!empty($etiquetas)) {
                $hito->syncEtiquetas($etiquetas);
            }

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
                'hito' => $hito->fresh(['entregables', 'camposPersonalizados.campoPersonalizado']),
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
     * Actualiza un hito existente con campos personalizados y jerarquía.
     */
    public function update(Hito $hito, array $data): array
    {
        DB::beginTransaction();
        try {
            // Guardar valores anteriores para notificaciones y validaciones
            $responsableAnterior = $hito->responsable_id;
            $estadoAnterior = $hito->estado;
            $parentIdAnterior = $hito->parent_id;

            // Separar datos relacionados
            $camposPersonalizados = $data['campos_personalizados'] ?? [];
            $etiquetas = $data['etiquetas'] ?? null;
            unset($data['entregables'], $data['campos_personalizados'], $data['etiquetas']); // Los entregables se gestionan por separado

            // Validar campos personalizados requeridos
            if (!empty($camposPersonalizados)) {
                $this->validarCamposPersonalizadosRequeridos($camposPersonalizados, 'hitos');
            }

            // Actualizar el hito
            $this->repository->update($hito, $data);

            // Guardar campos personalizados si existen
            if (!empty($camposPersonalizados)) {
                $hito->saveCamposPersonalizados($camposPersonalizados);
            }

            // Sincronizar etiquetas si se proporcionaron
            if ($etiquetas !== null) {
                $hito->syncEtiquetas($etiquetas);
            }

            // Si cambió el parent_id, recalcular jerarquía de descendientes
            if ($parentIdAnterior != $hito->parent_id) {
                $this->recalcularJerarquiaDescendientes($hito);
            }

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
                'hito' => $hito->fresh(['entregables', 'camposPersonalizados.campoPersonalizado', 'parent', 'children']),
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

            // Registrar cambio de estado en audit log y notificar
            if ($estadoAnterior != $nuevoEstado) {
                $hito->logStateChange('estado', $estadoAnterior, $nuevoEstado);
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
     * Mueve un hito para que sea hijo de otro hito.
     */
    public function moverAHijo(Hito $hito, int $nuevoParentId): array
    {
        DB::beginTransaction();
        try {
            // Validar que puede ser hijo del nuevo padre
            if (!$hito->puedeSerHijoDe($nuevoParentId)) {
                return [
                    'success' => false,
                    'message' => 'No se puede mover el hito: se crearía un ciclo en la jerarquía'
                ];
            }

            $hito->parent_id = $nuevoParentId;
            $hito->save();

            // Recalcular jerarquía de descendientes
            $this->recalcularJerarquiaDescendientes($hito);

            DB::commit();

            return [
                'success' => true,
                'hito' => $hito->fresh(['parent', 'children']),
                'message' => 'Hito movido exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al mover hito: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al mover el hito'
            ];
        }
    }

    /**
     * Mueve un hito a nivel raíz (sin padre).
     */
    public function moverARaiz(Hito $hito): array
    {
        DB::beginTransaction();
        try {
            $hito->parent_id = null;
            $hito->save();

            // Recalcular jerarquía de descendientes
            $this->recalcularJerarquiaDescendientes($hito);

            DB::commit();

            return [
                'success' => true,
                'hito' => $hito->fresh(['children']),
                'message' => 'Hito movido a raíz exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al mover hito a raíz: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al mover el hito a raíz'
            ];
        }
    }

    /**
     * Duplica un hito con todos sus hijos y campos personalizados.
     */
    public function duplicarConHijos(Hito $hito, array $datosNuevos = []): array
    {
        DB::beginTransaction();
        try {
            // Duplicar hito principal
            $nuevoHito = $this->repository->duplicar($hito, $datosNuevos);

            // Copiar campos personalizados
            $camposPersonalizados = $hito->getCamposPersonalizadosValues();
            if (!empty($camposPersonalizados)) {
                $nuevoHito->saveCamposPersonalizados($camposPersonalizados);
            }

            // Duplicar hijos recursivamente
            foreach ($hito->children as $hijo) {
                $this->duplicarHijoRecursivo($hijo, $nuevoHito->id);
            }

            DB::commit();

            return [
                'success' => true,
                'hito' => $nuevoHito->fresh(['entregables', 'children', 'camposPersonalizados.campoPersonalizado']),
                'message' => 'Hito duplicado con sus hijos exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al duplicar hito con hijos: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al duplicar el hito con sus hijos'
            ];
        }
    }

    /**
     * Duplica un hijo recursivamente.
     */
    private function duplicarHijoRecursivo(Hito $hijo, int $nuevoParentId): Hito
    {
        // Crear copia del hijo
        $nuevoHijo = $this->repository->duplicar($hijo, ['parent_id' => $nuevoParentId]);

        // Copiar campos personalizados
        $camposPersonalizados = $hijo->getCamposPersonalizadosValues();
        if (!empty($camposPersonalizados)) {
            $nuevoHijo->saveCamposPersonalizados($camposPersonalizados);
        }

        // Duplicar hijos del hijo recursivamente
        foreach ($hijo->children as $nieto) {
            $this->duplicarHijoRecursivo($nieto, $nuevoHijo->id);
        }

        return $nuevoHijo;
    }

    /**
     * Recalcula la jerarquía de todos los descendientes de un hito.
     */
    private function recalcularJerarquiaDescendientes(Hito $hito): void
    {
        foreach ($hito->getDescendientes() as $descendiente) {
            $descendiente->recalcularNivel();
            $descendiente->recalcularRuta();
        }
    }

    /**
     * Valida que los campos personalizados requeridos estén presentes.
     */
    private function validarCamposPersonalizadosRequeridos(array $valores, string $aplicarPara): void
    {
        // Obtener campos requeridos según el contexto
        $camposRequeridos = match ($aplicarPara) {
            'hitos' => $this->campoPersonalizadoRepository->getActivosParaHitos()->where('es_requerido', true),
            'entregables' => $this->campoPersonalizadoRepository->getActivosParaEntregables()->where('es_requerido', true),
            default => collect([])
        };

        // Validar que cada campo requerido tenga valor
        foreach ($camposRequeridos as $campo) {
            if (!isset($valores[$campo->id]) || empty($valores[$campo->id])) {
                throw new \Exception("El campo '{$campo->nombre}' es requerido");
            }
        }
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