<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\Entregable;
use Modules\Proyectos\Repositories\EntregableRepository;
use Modules\Proyectos\Repositories\CampoPersonalizadoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntregableService
{
    public function __construct(
        private EntregableRepository $repository,
        private CampoPersonalizadoRepository $campoPersonalizadoRepository
    ) {}

    /**
     * Crea un nuevo entregable con campos personalizados.
     */
    public function create(array $data): array
    {
        DB::beginTransaction();
        try {
            // Separar campos personalizados y etiquetas
            $camposPersonalizados = $data['campos_personalizados'] ?? [];
            $etiquetas = $data['etiquetas'] ?? [];
            unset($data['campos_personalizados'], $data['etiquetas']);

            // Validar campos personalizados requeridos
            $this->validarCamposPersonalizadosRequeridos($camposPersonalizados);

            // Crear el entregable
            $entregable = $this->repository->create($data);

            // Guardar campos personalizados si existen
            if (!empty($camposPersonalizados)) {
                $entregable->saveCamposPersonalizados($camposPersonalizados);
            }

            // Sincronizar etiquetas si existen
            if (!empty($etiquetas)) {
                $entregable->syncEtiquetas($etiquetas);
            }

            DB::commit();

            return [
                'success' => true,
                'entregable' => $entregable->fresh(['camposPersonalizados.campoPersonalizado']),
                'message' => 'Entregable creado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear entregable: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear el entregable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza un entregable con campos personalizados.
     */
    public function update(Entregable $entregable, array $data): array
    {
        DB::beginTransaction();
        try {
            // Separar campos personalizados y etiquetas
            $camposPersonalizados = $data['campos_personalizados'] ?? [];
            $etiquetas = $data['etiquetas'] ?? null;
            unset($data['campos_personalizados'], $data['etiquetas']);

            // Validar campos personalizados requeridos
            if (!empty($camposPersonalizados)) {
                $this->validarCamposPersonalizadosRequeridos($camposPersonalizados);
            }

            // Actualizar el entregable
            $entregable = $this->repository->update($entregable, $data);

            // Guardar campos personalizados si existen
            if (!empty($camposPersonalizados)) {
                $entregable->saveCamposPersonalizados($camposPersonalizados);
            }

            // Sincronizar etiquetas si se proporcionaron
            if ($etiquetas !== null) {
                $entregable->syncEtiquetas($etiquetas);
            }

            DB::commit();

            return [
                'success' => true,
                'entregable' => $entregable->fresh(['camposPersonalizados.campoPersonalizado']),
                'message' => 'Entregable actualizado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar entregable: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar el entregable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Marca un entregable como completado.
     */
    public function marcarComoCompletado(Entregable $entregable, int $usuarioId, ?string $notas = null): array
    {
        try {
            $entregable = $this->repository->marcarComoCompletado($entregable, $usuarioId, $notas);

            return [
                'success' => true,
                'entregable' => $entregable,
                'message' => 'Entregable completado exitosamente'
            ];
        } catch (\Exception $e) {
            Log::error('Error al completar entregable: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al completar el entregable'
            ];
        }
    }

    /**
     * Elimina un entregable.
     */
    public function delete(Entregable $entregable): array
    {
        DB::beginTransaction();
        try {
            $resultado = $this->repository->delete($entregable);

            DB::commit();

            return [
                'success' => $resultado,
                'message' => 'Entregable eliminado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar entregable: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar el entregable'
            ];
        }
    }

    /**
     * Valida que los campos personalizados requeridos estén presentes.
     */
    private function validarCamposPersonalizadosRequeridos(array $valores): void
    {
        // Obtener campos requeridos para entregables
        $camposRequeridos = $this->campoPersonalizadoRepository
            ->getActivosParaEntregables()
            ->where('es_requerido', true);

        // Validar que cada campo requerido tenga valor
        foreach ($camposRequeridos as $campo) {
            if (!isset($valores[$campo->id]) || empty($valores[$campo->id])) {
                throw new \Exception("El campo '{$campo->nombre}' es requerido");
            }
        }
    }
}