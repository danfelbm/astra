<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\ObligacionContrato;
use Modules\Proyectos\Models\Contrato;
use Modules\Proyectos\Repositories\ObligacionContratoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class ObligacionContratoService
{
    public function __construct(
        private ObligacionContratoRepository $repository
    ) {}

    /**
     * Crea una nueva obligación de contrato.
     */
    public function create(array $data): array
    {
        DB::beginTransaction();

        try {
            // Verificar que el contrato existe
            $contrato = Contrato::findOrFail($data['contrato_id']);

            // Verificar permisos sobre el contrato
            if (!auth()->user()->can('obligaciones.create')) {
                throw new \Exception('No tienes permisos para crear obligaciones');
            }

            // Procesar archivos adjuntos si existen
            if (isset($data['archivos'])) {
                $data['archivos_adjuntos'] = $this->procesarArchivos($data['archivos'], $contrato->id);
                unset($data['archivos']);
            }

            // Establecer valores por defecto
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            // Campos de responsable eliminados - deprecado

            // Crear la obligación
            $obligacion = $this->repository->create($data);

            // Lógica de porcentaje eliminada - campo deprecado

            // Registrar actividad
            activity()
                ->performedOn($obligacion)
                ->causedBy(auth()->user())
                ->withProperties(['contrato_id' => $contrato->id])
                ->log('Obligación creada: ' . $obligacion->titulo);

            // Notificaciones de responsable eliminadas - campo deprecado

            DB::commit();

            return [
                'success' => true,
                'obligacion' => $obligacion->load(['padre']),
                'message' => 'Obligación creada exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al crear la obligación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza una obligación existente.
     */
    public function update(ObligacionContrato $obligacion, array $data): array
    {
        DB::beginTransaction();

        try {
            // Verificar permisos
            if (!auth()->user()->can('obligaciones.edit')) {
                throw new \Exception('No tienes permisos para editar obligaciones');
            }

            // Procesar archivos adjuntos si existen nuevos
            if (isset($data['archivos'])) {
                $archivosExistentes = $obligacion->archivos_adjuntos ?? [];
                $nuevosArchivos = $this->procesarArchivos($data['archivos'], $obligacion->contrato_id);
                $data['archivos_adjuntos'] = array_merge($archivosExistentes, $nuevosArchivos);
                unset($data['archivos']);
            }

            // Si se está eliminando archivos
            if (isset($data['archivos_eliminar'])) {
                $this->eliminarArchivos($data['archivos_eliminar']);
                $archivosActuales = $obligacion->archivos_adjuntos ?? [];
                $data['archivos_adjuntos'] = array_diff($archivosActuales, $data['archivos_eliminar']);
                unset($data['archivos_eliminar']);
            }

            $data['updated_by'] = auth()->id();

            // Actualizar obligación
            $this->repository->update($obligacion, $data);

            // Lógica de estados y responsables eliminada - campos deprecados

            // Registrar actividad
            activity()
                ->performedOn($obligacion)
                ->causedBy(auth()->user())
                ->withProperties(['cambios' => $data])
                ->log('Obligación actualizada: ' . $obligacion->titulo);

            DB::commit();

            return [
                'success' => true,
                'obligacion' => $obligacion->fresh(['padre', 'hijos']),
                'message' => 'Obligación actualizada exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al actualizar la obligación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina una obligación y sus hijos.
     */
    public function delete(ObligacionContrato $obligacion): array
    {
        DB::beginTransaction();

        try {
            // Verificar permisos
            if (!auth()->user()->can('obligaciones.delete')) {
                throw new \Exception('No tienes permisos para eliminar obligaciones');
            }

            // Verificar si tiene hijos
            if ($obligacion->tiene_hijos) {
                $confirmacion = request()->input('confirmar_eliminar_hijos', false);

                if (!$confirmacion) {
                    return [
                        'success' => false,
                        'message' => 'Esta obligación tiene sub-obligaciones. ¿Deseas eliminar también todas las sub-obligaciones?',
                        'requiere_confirmacion' => true,
                        'total_hijos' => $obligacion->descendientes()->count()
                    ];
                }
            }

            // Eliminar archivos adjuntos
            if ($obligacion->archivos_adjuntos) {
                $this->eliminarArchivos($obligacion->archivos_adjuntos);
            }

            $titulo = $obligacion->titulo;
            $parentId = $obligacion->parent_id;

            // Eliminar (los hijos se eliminarán en cascada)
            $this->repository->delete($obligacion);

            // Lógica de porcentaje eliminada - campo deprecado

            // Registrar actividad
            activity()
                ->causedBy(auth()->user())
                ->log('Obligación eliminada: ' . $titulo);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Obligación eliminada exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al eliminar la obligación: ' . $e->getMessage()
            ];
        }
    }

    // Método marcarComoCumplida eliminado - estado deprecado

    /**
     * Reordena las obligaciones.
     */
    public function reordenar(array $ordenIds, int $contratoId, ?int $parentId = null): array
    {
        DB::beginTransaction();

        try {
            ObligacionContrato::reordenar($contratoId, $parentId, $ordenIds);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Obligaciones reordenadas exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al reordenar las obligaciones: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mueve una obligación a otro padre o posición.
     */
    public function mover(ObligacionContrato $obligacion, ?int $nuevoParentId, int $nuevoOrden): array
    {
        DB::beginTransaction();

        try {
            // Verificar permisos
            if (!auth()->user()->can('obligaciones.edit')) {
                throw new \Exception('No tienes permisos para mover obligaciones');
            }

            $result = $this->repository->mover($obligacion, $nuevoParentId, $nuevoOrden);

            if (!$result) {
                throw new \Exception('No se puede mover la obligación a uno de sus descendientes');
            }

            // Lógica de porcentaje eliminada - campo deprecado

            DB::commit();

            return [
                'success' => true,
                'obligacion' => $obligacion->fresh(['padre', 'hijos']),
                'message' => 'Obligación movida exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al mover la obligación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Duplica una obligación con todos sus hijos.
     */
    public function duplicar(ObligacionContrato $obligacion, ?int $nuevoContratoId = null): array
    {
        DB::beginTransaction();

        try {
            // Verificar permisos
            if (!auth()->user()->can('obligaciones.create')) {
                throw new \Exception('No tienes permisos para duplicar obligaciones');
            }

            $nuevaObligacion = $obligacion->duplicar($nuevoContratoId);

            // Registrar actividad
            activity()
                ->performedOn($nuevaObligacion)
                ->causedBy(auth()->user())
                ->withProperties(['original_id' => $obligacion->id])
                ->log('Obligación duplicada: ' . $nuevaObligacion->titulo);

            DB::commit();

            return [
                'success' => true,
                'obligacion' => $nuevaObligacion->load(['hijos']),
                'message' => 'Obligación duplicada exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al duplicar la obligación: ' . $e->getMessage()
            ];
        }
    }

    // Método actualizarEstadoMasivo eliminado - estado deprecado

    // Método actualizarObligacionesVencidas eliminado - estado deprecado

    /**
     * Procesa archivos adjuntos.
     */
    protected function procesarArchivos(array $archivos, int $contratoId): array
    {
        $rutas = [];

        foreach ($archivos as $archivo) {
            if ($archivo instanceof UploadedFile) {
                $nombreOriginal = $archivo->getClientOriginalName();
                $extension = $archivo->getClientOriginalExtension();
                $nombreArchivo = time() . '_' . uniqid() . '.' . $extension;

                $ruta = $archivo->storeAs(
                    "contratos/{$contratoId}/obligaciones",
                    $nombreArchivo,
                    'public'
                );

                $rutas[] = [
                    'ruta' => $ruta,
                    'nombre_original' => $nombreOriginal,
                    'tamaño' => $archivo->getSize(),
                    'tipo' => $archivo->getMimeType(),
                    'subido_por' => auth()->id(),
                    'subido_at' => now()->toISOString()
                ];
            }
        }

        return $rutas;
    }

    /**
     * Elimina archivos adjuntos del storage.
     */
    protected function eliminarArchivos(array $archivos): void
    {
        foreach ($archivos as $archivo) {
            if (is_array($archivo) && isset($archivo['ruta'])) {
                Storage::disk('public')->delete($archivo['ruta']);
            } elseif (is_string($archivo)) {
                Storage::disk('public')->delete($archivo);
            }
        }
    }

    // Métodos de notificación eliminados - campos de estado y responsable deprecados
}