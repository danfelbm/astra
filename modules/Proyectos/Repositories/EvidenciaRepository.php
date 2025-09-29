<?php

namespace Modules\Proyectos\Repositories;

use Modules\Proyectos\Models\Evidencia;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EvidenciaRepository
{
    /**
     * Crea una nueva evidencia.
     */
    public function create(array $data): Evidencia
    {
        return Evidencia::create($data);
    }

    /**
     * Actualiza una evidencia.
     */
    public function update(Evidencia $evidencia, array $data): bool
    {
        return $evidencia->update($data);
    }

    /**
     * Elimina una evidencia.
     */
    public function delete(Evidencia $evidencia): bool
    {
        return $evidencia->delete();
    }

    /**
     * Obtiene evidencias por contrato paginadas.
     */
    public function getByContratoPaginated(int $contratoId, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return Evidencia::query()
            ->whereHas('obligacion', function ($query) use ($contratoId) {
                $query->where('contrato_id', $contratoId);
            })
            ->with(['usuario', 'obligacion', 'entregables'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('descripcion', 'like', "%{$search}%")
                      ->orWhere('archivo_nombre', 'like', "%{$search}%")
                      ->orWhereHas('obligacion', function ($q2) use ($search) {
                          $q2->where('titulo', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->tipo, function ($query, $tipo) {
                $query->where('tipo_evidencia', $tipo);
            })
            ->when($request->estado, function ($query, $estado) {
                $query->where('estado', $estado);
            })
            ->when($request->obligacion_id, function ($query, $obligacionId) {
                $query->where('obligacion_id', $obligacionId);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Obtiene evidencias por obligación.
     */
    public function getByObligacion(int $obligacionId): Collection
    {
        return Evidencia::where('obligacion_id', $obligacionId)
            ->with(['usuario', 'entregables'])
            ->latest()
            ->get();
    }

    /**
     * Obtiene evidencias del usuario.
     */
    public function getByUsuario(int $userId, Request $request = null): Collection
    {
        $query = Evidencia::where('user_id', $userId)
            ->with(['obligacion.contrato', 'entregables']);

        if ($request) {
            $query->when($request->estado, function ($q, $estado) {
                $q->where('estado', $estado);
            })
            ->when($request->tipo, function ($q, $tipo) {
                $q->where('tipo_evidencia', $tipo);
            });
        }

        return $query->latest()->get();
    }

    /**
     * Obtiene estadísticas de evidencias por contrato.
     */
    public function getEstadisticasPorContrato(int $contratoId): array
    {
        $evidencias = Evidencia::query()
            ->whereHas('obligacion', function ($query) use ($contratoId) {
                $query->where('contrato_id', $contratoId);
            })
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN estado = "pendiente" THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = "aprobada" THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN estado = "rechazada" THEN 1 ELSE 0 END) as rechazadas
            ')
            ->first();

        $porTipo = Evidencia::query()
            ->whereHas('obligacion', function ($query) use ($contratoId) {
                $query->where('contrato_id', $contratoId);
            })
            ->selectRaw('tipo_evidencia, COUNT(*) as cantidad')
            ->groupBy('tipo_evidencia')
            ->pluck('cantidad', 'tipo_evidencia')
            ->toArray();

        return [
            'total' => $evidencias->total ?? 0,
            'pendientes' => $evidencias->pendientes ?? 0,
            'aprobadas' => $evidencias->aprobadas ?? 0,
            'rechazadas' => $evidencias->rechazadas ?? 0,
            'por_tipo' => $porTipo,
            'porcentaje_aprobacion' => $evidencias->total > 0
                ? round(($evidencias->aprobadas / $evidencias->total) * 100, 2)
                : 0
        ];
    }

    /**
     * Obtiene estadísticas de evidencias por obligación.
     */
    public function getEstadisticasPorObligacion(int $obligacionId): array
    {
        $evidencias = Evidencia::where('obligacion_id', $obligacionId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN estado = "pendiente" THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = "aprobada" THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN estado = "rechazada" THEN 1 ELSE 0 END) as rechazadas
            ')
            ->first();

        return [
            'total' => $evidencias->total ?? 0,
            'pendientes' => $evidencias->pendientes ?? 0,
            'aprobadas' => $evidencias->aprobadas ?? 0,
            'rechazadas' => $evidencias->rechazadas ?? 0
        ];
    }

    /**
     * Obtiene evidencias pendientes de revisión.
     */
    public function getPendientesRevision(int $limit = 10): Collection
    {
        return Evidencia::pendientes()
            ->with(['obligacion.contrato', 'usuario'])
            ->oldest()
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene evidencias recientes del usuario.
     */
    public function getRecientesPorUsuario(int $userId, int $limit = 5): Collection
    {
        return Evidencia::where('user_id', $userId)
            ->with(['obligacion', 'entregables'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Busca evidencias por término.
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Evidencia::query()
            ->where(function ($query) use ($term) {
                $query->where('descripcion', 'like', "%{$term}%")
                      ->orWhere('archivo_nombre', 'like', "%{$term}%")
                      ->orWhereHas('obligacion', function ($q) use ($term) {
                          $q->where('titulo', 'like', "%{$term}%");
                      })
                      ->orWhereHas('usuario', function ($q) use ($term) {
                          $q->where('name', 'like', "%{$term}%");
                      });
            })
            ->with(['obligacion', 'usuario'])
            ->limit($limit)
            ->get();
    }

    /**
     * Verifica si existe evidencia para una obligación.
     */
    public function existeParaObligacion(int $obligacionId): bool
    {
        return Evidencia::where('obligacion_id', $obligacionId)->exists();
    }

    /**
     * Verifica si existe evidencia aprobada para una obligación.
     */
    public function existeAprobadaParaObligacion(int $obligacionId): bool
    {
        return Evidencia::where('obligacion_id', $obligacionId)
            ->where('estado', 'aprobada')
            ->exists();
    }
}