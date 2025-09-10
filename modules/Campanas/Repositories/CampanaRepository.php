<?php

namespace Modules\Campanas\Repositories;

use Modules\Campanas\Models\Campana;
use Modules\Campanas\Models\CampanaEnvio;
use Modules\Campanas\Models\CampanaMetrica;
use Modules\Core\Services\TenantService;
use Modules\Core\Traits\HasAdvancedFilters;
use Modules\Core\Traits\HasGeographicFilters;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CampanaRepository
{
    use HasAdvancedFilters, HasGeographicFilters;

    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Obtener campañas paginadas con filtros
     */
    public function getAllPaginated(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = Campana::query()
            ->with(['segment', 'plantillaEmail', 'plantillaWhatsApp', 'createdBy', 'metrica'])
            ->withCount('envios');
        
        // Aplicar filtros básicos
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->filled('tipo') && $request->tipo !== 'all') {
            $query->where('tipo', $request->tipo);
        }
        
        if ($request->filled('estado') && $request->estado !== 'all') {
            $query->where('estado', $request->estado);
        }
        
        // Aplicar filtros avanzados si existen
        if ($request->filled('advanced_filters')) {
            $allowedFields = [
                'nombre', 'descripcion', 'tipo', 'estado', 
                'fecha_programada', 'fecha_inicio', 'fecha_fin',
                'segment_id', 'created_at'
            ];
            $quickSearchFields = ['nombre', 'descripcion'];
            $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);
        }
        
        // Ordenamiento
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Obtener configuración de campos para filtros avanzados
     */
    public function getFilterFieldsConfig(): array
    {
        return [
            [
                'name' => 'nombre',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            [
                'name' => 'descripcion',
                'label' => 'Descripción',
                'type' => 'text',
            ],
            [
                'name' => 'tipo',
                'label' => 'Tipo',
                'type' => 'select',
                'options' => [
                    ['value' => 'email', 'label' => 'Email'],
                    ['value' => 'whatsapp', 'label' => 'WhatsApp'],
                    ['value' => 'ambos', 'label' => 'Ambos'],
                ],
            ],
            [
                'name' => 'estado',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'borrador', 'label' => 'Borrador'],
                    ['value' => 'programada', 'label' => 'Programada'],
                    ['value' => 'enviando', 'label' => 'Enviando'],
                    ['value' => 'completada', 'label' => 'Completada'],
                    ['value' => 'pausada', 'label' => 'Pausada'],
                    ['value' => 'cancelada', 'label' => 'Cancelada'],
                ],
            ],
            [
                'name' => 'fecha_programada',
                'label' => 'Fecha programada',
                'type' => 'date',
            ],
            [
                'name' => 'created_at',
                'label' => 'Fecha de creación',
                'type' => 'date',
            ],
        ];
    }

    /**
     * Buscar campaña por ID
     */
    public function find(int $id): ?Campana
    {
        return Campana::with([
            'segment', 
            'plantillaEmail', 
            'plantillaWhatsApp', 
            'createdBy',
            'metrica'
        ])->find($id);
    }

    /**
     * Buscar campaña con envíos
     */
    public function findWithEnvios(int $id): ?Campana
    {
        return Campana::with([
            'segment', 
            'plantillaEmail', 
            'plantillaWhatsApp', 
            'createdBy',
            'metrica',
            'envios' => function ($query) {
                $query->latest()->limit(100);
            }
        ])->find($id);
    }

    /**
     * Crear campaña
     */
    public function create(array $data): Campana
    {
        if (Auth::check()) {
            $data['created_by'] = Auth::id();
        }
        
        return Campana::create($data);
    }

    /**
     * Actualizar campaña
     */
    public function update(Campana $campana, array $data): bool
    {
        return $campana->update($data);
    }

    /**
     * Eliminar campaña
     */
    public function delete(Campana $campana): bool
    {
        // Solo permitir eliminar si está en estado borrador
        if ($campana->estado !== 'borrador') {
            return false;
        }
        
        // Eliminar en transacción
        return DB::transaction(function () use ($campana) {
            // Los envíos y métricas se eliminarán en cascada por las foreign keys
            return $campana->delete();
        });
    }

    /**
     * Obtener envíos paginados de una campaña
     */
    public function getEnviosPaginated(Campana $campana, Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $query = $campana->envios()
            ->with(['user']);
        
        // Filtros
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('destinatario', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function ($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }
        
        if ($request->filled('tipo') && $request->tipo !== 'all') {
            $query->where('tipo', $request->tipo);
        }
        
        if ($request->filled('estado') && $request->estado !== 'all') {
            $query->where('estado', $request->estado);
        }
        
        // Ordenamiento
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Obtener métricas de una campaña
     */
    public function getMetricas(Campana $campana): CampanaMetrica
    {
        return CampanaMetrica::obtenerOActualizar($campana);
    }

    /**
     * Obtener estadísticas de campañas
     */
    public function getEstadisticas(): array
    {
        $tenantId = $this->tenantService->getCurrentTenant()?->id;
        
        $query = Campana::query();
        
        if ($tenantId && !Auth::user()->hasRole('super_admin')) {
            $query->where('tenant_id', $tenantId);
        }
        
        return [
            'total' => $query->count(),
            'borradores' => (clone $query)->where('estado', 'borrador')->count(),
            'programadas' => (clone $query)->where('estado', 'programada')->count(),
            'enviando' => (clone $query)->where('estado', 'enviando')->count(),
            'completadas' => (clone $query)->where('estado', 'completada')->count(),
            'pausadas' => (clone $query)->where('estado', 'pausada')->count(),
            'canceladas' => (clone $query)->where('estado', 'cancelada')->count(),
            'ultimo_mes' => (clone $query)->where('created_at', '>=', now()->subMonth())->count(),
        ];
    }

    /**
     * Obtener campañas activas (enviando o programadas)
     */
    public function getActivas()
    {
        return Campana::whereIn('estado', ['enviando', 'programada'])
            ->with(['segment', 'plantillaEmail', 'plantillaWhatsApp'])
            ->orderBy('fecha_programada')
            ->get();
    }

    /**
     * Obtener campañas programadas que deben iniciarse
     */
    public function getProgramadasParaIniciar()
    {
        return Campana::where('estado', 'programada')
            ->where('fecha_programada', '<=', now())
            ->with(['segment', 'plantillaEmail', 'plantillaWhatsApp'])
            ->get();
    }

    /**
     * Actualizar estado de campaña
     */
    public function updateEstado(Campana $campana, string $estado): bool
    {
        return $campana->update(['estado' => $estado]);
    }

    /**
     * Marcar inicio de campaña
     */
    public function marcarInicio(Campana $campana): bool
    {
        return $campana->update([
            'estado' => 'enviando',
            'fecha_inicio' => now(),
        ]);
    }

    /**
     * Marcar fin de campaña
     */
    public function marcarFin(Campana $campana): bool
    {
        return $campana->update([
            'estado' => 'completada',
            'fecha_fin' => now(),
        ]);
    }

    /**
     * Pausar campaña
     */
    public function pausar(Campana $campana): bool
    {
        if (!in_array($campana->estado, ['enviando', 'programada'])) {
            return false;
        }
        
        return $campana->update(['estado' => 'pausada']);
    }

    /**
     * Reanudar campaña
     */
    public function reanudar(Campana $campana): bool
    {
        if ($campana->estado !== 'pausada') {
            return false;
        }
        
        return $campana->update(['estado' => 'enviando']);
    }

    /**
     * Cancelar campaña
     */
    public function cancelar(Campana $campana): bool
    {
        if (!in_array($campana->estado, ['programada', 'enviando', 'pausada'])) {
            return false;
        }
        
        return $campana->update([
            'estado' => 'cancelada',
            'fecha_fin' => now(),
        ]);
    }

    /**
     * Duplicar campaña
     */
    public function duplicar(Campana $campana): Campana
    {
        $nueva = $campana->replicate();
        $nueva->nombre = $campana->nombre . ' (Copia)';
        $nueva->estado = 'borrador';
        $nueva->fecha_programada = null;
        $nueva->fecha_inicio = null;
        $nueva->fecha_fin = null;
        $nueva->created_by = Auth::id();
        $nueva->save();
        
        return $nueva;
    }

    /**
     * Obtener configuración de campos para filtros de envíos
     */
    public function getEnviosFilterFieldsConfig(): array
    {
        return [
            [
                'name' => 'destinatario',
                'label' => 'Destinatario',
                'type' => 'text',
            ],
            [
                'name' => 'tipo',
                'label' => 'Tipo',
                'type' => 'select',
                'options' => [
                    ['value' => 'email', 'label' => 'Email'],
                    ['value' => 'whatsapp', 'label' => 'WhatsApp'],
                ],
            ],
            [
                'name' => 'estado',
                'label' => 'Estado',
                'type' => 'select',
                'options' => [
                    ['value' => 'pendiente', 'label' => 'Pendiente'],
                    ['value' => 'enviando', 'label' => 'Enviando'],
                    ['value' => 'enviado', 'label' => 'Enviado'],
                    ['value' => 'abierto', 'label' => 'Abierto'],
                    ['value' => 'click', 'label' => 'Con clicks'],
                    ['value' => 'fallido', 'label' => 'Fallido'],
                ],
            ],
            [
                'name' => 'fecha_enviado',
                'label' => 'Fecha de envío',
                'type' => 'date',
            ],
        ];
    }
}