<?php

namespace Modules\Proyectos\Repositories;

use Modules\Proyectos\Models\CampoPersonalizado;
use Modules\Proyectos\Models\ValorCampoPersonalizado;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CampoPersonalizadoRepository
{
    /**
     * Obtiene todos los campos personalizados paginados.
     */
    public function getAllPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return CampoPersonalizado::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            })
            ->when($request->tipo, function ($query, $tipo) {
                $query->where('tipo', $tipo);
            })
            ->when($request->has('activo'), function ($query) use ($request) {
                $query->where('activo', $request->boolean('activo'));
            })
            ->when($request->has('es_requerido'), function ($query) use ($request) {
                $query->where('es_requerido', $request->boolean('es_requerido'));
            })
            ->ordenado()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Obtiene todos los campos activos.
     */
    public function getActivos(): Collection
    {
        return CampoPersonalizado::activos()
            ->ordenado()
            ->get();
    }

    /**
     * Obtiene todos los campos requeridos.
     */
    public function getRequeridos(): Collection
    {
        return CampoPersonalizado::requeridos()
            ->activos()
            ->ordenado()
            ->get();
    }

    /**
     * Encuentra un campo por ID.
     */
    public function find(int $id): ?CampoPersonalizado
    {
        return CampoPersonalizado::find($id);
    }

    /**
     * Encuentra un campo por slug.
     */
    public function findBySlug(string $slug): ?CampoPersonalizado
    {
        return CampoPersonalizado::where('slug', $slug)->first();
    }

    /**
     * Crea un nuevo campo personalizado.
     */
    public function create(array $data): CampoPersonalizado
    {
        return CampoPersonalizado::create($data);
    }

    /**
     * Actualiza un campo personalizado.
     */
    public function update(CampoPersonalizado $campo, array $data): bool
    {
        return $campo->update($data);
    }

    /**
     * Elimina un campo personalizado.
     */
    public function delete(CampoPersonalizado $campo): bool
    {
        return $campo->delete();
    }

    /**
     * Obtiene los valores de un campo para un proyecto específico.
     */
    public function getValoresParaProyecto(int $proyectoId): Collection
    {
        return ValorCampoPersonalizado::with('campoPersonalizado')
            ->where('proyecto_id', $proyectoId)
            ->get();
    }

    /**
     * Guarda múltiples valores de campos para un proyecto.
     */
    public function guardarValoresParaProyecto(int $proyectoId, array $valores): void
    {
        foreach ($valores as $campoId => $valor) {
            ValorCampoPersonalizado::updateOrCreate(
                [
                    'proyecto_id' => $proyectoId,
                    'campo_personalizado_id' => $campoId
                ],
                ['valor' => $valor]
            );
        }
    }

    /**
     * Reordena los campos personalizados.
     */
    public function reordenar(array $orden): void
    {
        foreach ($orden as $index => $campoId) {
            CampoPersonalizado::where('id', $campoId)
                ->update(['orden' => $index + 1]);
        }
    }

    /**
     * Obtiene campos por tipo.
     */
    public function getPorTipo(string $tipo): Collection
    {
        return CampoPersonalizado::tipo($tipo)
            ->activos()
            ->ordenado()
            ->get();
    }

    /**
     * Verifica si existe un campo con el slug dado.
     */
    public function existeSlug(string $slug, ?int $exceptId = null): bool
    {
        $query = CampoPersonalizado::where('slug', $slug);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Obtiene el siguiente orden disponible.
     */
    public function getSiguienteOrden(): int
    {
        return CampoPersonalizado::max('orden') + 1;
    }

    /**
     * Obtiene campos activos para hitos.
     */
    public function getActivosParaHitos(): Collection
    {
        return CampoPersonalizado::activos()
            ->paraHitos()
            ->ordenado()
            ->get();
    }

    /**
     * Obtiene campos activos para entregables.
     */
    public function getActivosParaEntregables(): Collection
    {
        return CampoPersonalizado::activos()
            ->paraEntregables()
            ->ordenado()
            ->get();
    }

    /**
     * Guarda múltiples valores de campos para un hito.
     */
    public function guardarValoresParaHito(int $hitoId, array $valores): void
    {
        foreach ($valores as $campoId => $valor) {
            ValorCampoPersonalizado::updateOrCreate(
                [
                    'hito_id' => $hitoId,
                    'campo_personalizado_id' => $campoId
                ],
                ['valor' => $valor]
            );
        }
    }

    /**
     * Guarda múltiples valores de campos para un entregable.
     */
    public function guardarValoresParaEntregable(int $entregableId, array $valores): void
    {
        foreach ($valores as $campoId => $valor) {
            ValorCampoPersonalizado::updateOrCreate(
                [
                    'entregable_id' => $entregableId,
                    'campo_personalizado_id' => $campoId
                ],
                ['valor' => $valor]
            );
        }
    }

    /**
     * Obtiene los valores de campos para un hito específico.
     */
    public function getValoresParaHito(int $hitoId): Collection
    {
        return ValorCampoPersonalizado::with('campoPersonalizado')
            ->where('hito_id', $hitoId)
            ->get();
    }

    /**
     * Obtiene los valores de campos para un entregable específico.
     */
    public function getValoresParaEntregable(int $entregableId): Collection
    {
        return ValorCampoPersonalizado::with('campoPersonalizado')
            ->where('entregable_id', $entregableId)
            ->get();
    }
}