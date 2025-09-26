<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\Entregable;
use Modules\Proyectos\Repositories\EntregableRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntregableService
{
    public function __construct(
        private EntregableRepository $repository
    ) {}

    /**
     * Crea un nuevo entregable.
     */
    public function create(array $data): array
    {
        DB::beginTransaction();
        try {
            $entregable = $this->repository->create($data);

            DB::commit();

            return [
                'success' => true,
                'entregable' => $entregable,
                'message' => 'Entregable creado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear entregable: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear el entregable'
            ];
        }
    }

    /**
     * Actualiza un entregable.
     */
    public function update(Entregable $entregable, array $data): array
    {
        DB::beginTransaction();
        try {
            $entregable = $this->repository->update($entregable, $data);

            DB::commit();

            return [
                'success' => true,
                'entregable' => $entregable,
                'message' => 'Entregable actualizado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar entregable: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar el entregable'
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
}