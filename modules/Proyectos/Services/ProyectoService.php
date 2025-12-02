<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\CampoPersonalizado;
use Modules\Proyectos\Models\ValorCampoPersonalizado;
use Modules\Proyectos\Repositories\ProyectoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProyectoService
{
    public function __construct(
        private ProyectoRepository $repository,
        private ProyectoNotificationService $notificationService,
        private EtiquetaService $etiquetaService
    ) {}

    /**
     * Crea un nuevo proyecto con sus campos personalizados.
     */
    public function create(array $data): array
    {
        DB::beginTransaction();
        try {
            // Separar campos personalizados, etiquetas y gestores del resto de datos
            $camposPersonalizados = $data['campos_personalizados'] ?? [];
            $etiquetas = $data['etiquetas'] ?? [];
            $gestores = $data['gestores'] ?? [];
            $crearHitosIniciales = $data['crear_hitos_iniciales'] ?? false;
            $tipoProyecto = $data['tipo_proyecto'] ?? null;
            unset($data['campos_personalizados']);
            unset($data['etiquetas']);
            unset($data['gestores']);
            unset($data['crear_hitos_iniciales']);
            unset($data['tipo_proyecto']);

            // Crear el proyecto
            $proyecto = $this->repository->create($data);

            // Guardar campos personalizados si existen
            if (!empty($camposPersonalizados)) {
                $this->guardarCamposPersonalizados($proyecto, $camposPersonalizados);
            }

            // Sincronizar etiquetas si existen
            if (!empty($etiquetas)) {
                $proyecto->sincronizarEtiquetas($etiquetas);
            }

            // Sincronizar gestores si existen
            if (!empty($gestores)) {
                $proyecto->sincronizarGestores($gestores);
            }

            // Crear hitos iniciales si se solicitó
            if ($crearHitosIniciales && $tipoProyecto) {
                $this->crearHitosIniciales($proyecto, $tipoProyecto);
            }

            // Notificar si hay un responsable asignado
            if ($proyecto->responsable_id) {
                $this->notificationService->notificarAsignacion($proyecto);
            }

            DB::commit();

            return [
                'success' => true,
                'proyecto' => $proyecto,
                'message' => 'Proyecto creado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear proyecto: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al crear el proyecto: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza un proyecto existente.
     */
    public function update(Proyecto $proyecto, array $data): array
    {
        DB::beginTransaction();
        try {
            // Guardar el responsable anterior para notificación
            $responsableAnterior = $proyecto->responsable_id;

            // Separar campos personalizados, etiquetas y gestores del resto de datos
            $camposPersonalizados = $data['campos_personalizados'] ?? [];
            $etiquetas = $data['etiquetas'] ?? null;
            $gestores = $data['gestores'] ?? null;
            unset($data['campos_personalizados']);
            unset($data['etiquetas']);
            unset($data['gestores']);

            // Actualizar el proyecto
            $this->repository->update($proyecto, $data);

            // Actualizar campos personalizados si existen
            if (!empty($camposPersonalizados)) {
                $this->guardarCamposPersonalizados($proyecto, $camposPersonalizados);
            }

            // Sincronizar etiquetas si se proporcionaron
            if ($etiquetas !== null) {
                $proyecto->sincronizarEtiquetas($etiquetas);
            }

            // Sincronizar gestores si se proporcionaron
            if ($gestores !== null) {
                $proyecto->sincronizarGestores($gestores);
            }

            // Notificar cambio de responsable si aplica
            if ($responsableAnterior != $proyecto->responsable_id && $proyecto->responsable_id) {
                $this->notificationService->notificarAsignacion($proyecto);
            }

            // Notificar cambio de estado si aplica
            if ($proyecto->wasChanged('estado')) {
                $this->notificationService->notificarCambioEstado($proyecto);
            }

            DB::commit();

            return [
                'success' => true,
                'proyecto' => $proyecto->fresh(),
                'message' => 'Proyecto actualizado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar proyecto: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al actualizar el proyecto: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina un proyecto.
     */
    public function delete(Proyecto $proyecto): bool
    {
        DB::beginTransaction();
        try {
            // Eliminar valores de campos personalizados
            $proyecto->camposPersonalizados()->delete();

            // Eliminar el proyecto
            $result = $this->repository->delete($proyecto);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar proyecto: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Asigna un responsable al proyecto.
     */
    public function asignarResponsable(Proyecto $proyecto, int $responsableId): array
    {
        try {
            $responsableAnterior = $proyecto->responsable_id;

            $proyecto->responsable_id = $responsableId;
            $proyecto->updated_by = auth()->id();
            $proyecto->save();

            // Notificar al nuevo responsable
            if ($responsableAnterior != $responsableId) {
                $this->notificationService->notificarAsignacion($proyecto);
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
     * Actualiza solo los campos personalizados de un proyecto.
     */
    public function actualizarCamposPersonalizados(Proyecto $proyecto, array $campos): array
    {
        DB::beginTransaction();
        try {
            $this->guardarCamposPersonalizados($proyecto, $campos);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Campos personalizados actualizados exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar campos personalizados: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al actualizar campos personalizados'
            ];
        }
    }

    /**
     * Guarda los valores de campos personalizados para un proyecto.
     */
    private function guardarCamposPersonalizados(Proyecto $proyecto, array $valores): void
    {
        foreach ($valores as $key => $valor) {
            // El campo puede venir por ID o por slug
            if (is_numeric($key)) {
                // Si es numérico, es un ID
                $campo = CampoPersonalizado::find($key);
            } else {
                // Si no es numérico, es un slug
                $campo = CampoPersonalizado::where('slug', $key)->first();
            }

            if (!$campo) {
                continue;
            }

            // Manejar archivos si es necesario
            if ($campo->tipo === 'file' && $valor instanceof \Illuminate\Http\UploadedFile) {
                $valor = $this->guardarArchivo($valor, $proyecto);
            }

            // Guardar o actualizar el valor
            ValorCampoPersonalizado::updateOrCreate(
                [
                    'proyecto_id' => $proyecto->id,
                    'campo_personalizado_id' => $campo->id
                ],
                ['valor' => $valor]
            );
        }
    }

    /**
     * Guarda un archivo subido.
     */
    private function guardarArchivo($file, Proyecto $proyecto): string
    {
        $path = $file->store(
            'proyectos/' . $proyecto->id . '/archivos',
            'public'
        );

        return $path;
    }

    /**
     * Cambia el estado de un proyecto.
     */
    public function cambiarEstado(Proyecto $proyecto, string $nuevoEstado): array
    {
        try {
            $estadoAnterior = $proyecto->estado;

            $proyecto->estado = $nuevoEstado;
            $proyecto->updated_by = auth()->id();
            $proyecto->save();

            // Registrar cambio de estado en audit log y notificar
            if ($estadoAnterior != $nuevoEstado) {
                $proyecto->logStateChange('estado', $estadoAnterior, $nuevoEstado);
                $this->notificationService->notificarCambioEstado($proyecto);
            }

            return [
                'success' => true,
                'message' => 'Estado actualizado exitosamente'
            ];
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al cambiar estado del proyecto'
            ];
        }
    }

    /**
     * Duplica un proyecto existente.
     */
    public function duplicar(Proyecto $proyecto, string $nuevoNombre = null): array
    {
        DB::beginTransaction();
        try {
            // Copiar datos del proyecto original
            $datosNuevoProyecto = $proyecto->toArray();

            // Modificar datos para el nuevo proyecto
            unset($datosNuevoProyecto['id']);
            $datosNuevoProyecto['nombre'] = $nuevoNombre ?? $proyecto->nombre . ' (Copia)';
            $datosNuevoProyecto['estado'] = 'planificacion';
            $datosNuevoProyecto['created_by'] = auth()->id();
            $datosNuevoProyecto['updated_by'] = auth()->id();
            $datosNuevoProyecto['created_at'] = now();
            $datosNuevoProyecto['updated_at'] = now();

            // Crear el nuevo proyecto
            $nuevoProyecto = Proyecto::create($datosNuevoProyecto);

            // Copiar campos personalizados
            $valoresCampos = $proyecto->camposPersonalizados()->with('campoPersonalizado')->get();

            foreach ($valoresCampos as $valor) {
                ValorCampoPersonalizado::create([
                    'proyecto_id' => $nuevoProyecto->id,
                    'campo_personalizado_id' => $valor->campo_personalizado_id,
                    'valor' => $valor->valor
                ]);
            }

            // Copiar etiquetas
            $etiquetaIds = $proyecto->etiquetas->pluck('id')->toArray();
            if (!empty($etiquetaIds)) {
                $nuevoProyecto->sincronizarEtiquetas($etiquetaIds);
            }

            DB::commit();

            return [
                'success' => true,
                'proyecto' => $nuevoProyecto,
                'message' => 'Proyecto duplicado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al duplicar proyecto: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al duplicar el proyecto'
            ];
        }
    }

    /**
     * Crea hitos iniciales basados en plantillas predefinidas.
     */
    protected function crearHitosIniciales($proyecto, string $tipoProyecto): void
    {
        $plantillas = config('proyectos.plantillas_hitos');

        // Si no existe plantilla para este tipo, usar la genérica
        $plantilla = $plantillas[$tipoProyecto] ?? $plantillas['generico'] ?? [];

        if (empty($plantilla)) {
            return;
        }

        $orden = 1;

        foreach ($plantilla as $nombreHito => $entregables) {
            // Crear el hito
            $hito = $proyecto->hitos()->create([
                'nombre' => $nombreHito,
                'estado' => 'pendiente',
                'orden' => $orden,
                'porcentaje_completado' => 0,
                'responsable_id' => $proyecto->responsable_id,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Crear los entregables del hito
            $ordenEntregable = 1;
            foreach ($entregables as $nombreEntregable) {
                $hito->entregables()->create([
                    'nombre' => $nombreEntregable,
                    'estado' => 'pendiente',
                    'prioridad' => 'media',
                    'orden' => $ordenEntregable,
                    'responsable_id' => $proyecto->responsable_id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                $ordenEntregable++;
            }

            $orden++;
        }
    }
}