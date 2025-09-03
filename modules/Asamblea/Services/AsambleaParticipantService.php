<?php

namespace Modules\Asamblea\Services;

use Modules\Asamblea\Models\Asamblea;
use Modules\Core\Models\User;
use Modules\Core\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class AsambleaParticipantService
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Obtener usuarios disponibles para asignar a la asamblea
     */
    public function getAvailableUsers(Asamblea $asamblea, ?string $search = null, int $page = 1): array
    {
        // Obtener IDs de participantes ya asignados
        $relation = Auth::user()->hasRole('super_admin') ? $asamblea->allParticipantes() : $asamblea->participantes();
        $participantesAsignadosIds = $relation->pluck('usuario_id');
        
        // Buscar usuarios disponibles con paginación
        $query = User::where('activo', true)
            ->whereNotIn('id', $participantesAsignadosIds);
        
        // Aplicar búsqueda si existe (nombre, email, documento de identidad, teléfono)
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('documento_identidad', 'like', '%' . $search . '%')
                  ->orWhere('telefono', 'like', '%' . $search . '%');
            });
        }
        
        // Paginar resultados (máximo 50 por página para evitar sobrecarga)
        // Incluir campos adicionales para mostrar en el modal
        $participantesDisponibles = $query
            ->select('id', 'name', 'email', 'documento_identidad', 'telefono')
            ->orderBy('name')
            ->paginate(50);

        return [
            'participantes_disponibles' => $participantesDisponibles,
            'search' => $search ?? '',
        ];
    }

    /**
     * Asignar participantes a la asamblea
     */
    public function assignParticipants(Asamblea $asamblea, array $participanteIds, ?string $tipoParticipacion = null): bool
    {
        // Obtener el tenant_id actual
        $currentTenant = $this->tenantService->getCurrentTenant();
        $tenantId = $currentTenant ? $currentTenant->id : 1; // Default a 1 si no hay tenant
        
        // Preparar los datos para attach con tenant_id y tipo de participación
        $attachData = [];
        $tipoParticipacion = $tipoParticipacion ?? 'asistente';
        
        foreach ($participanteIds as $participanteId) {
            $attachData[$participanteId] = [
                'tenant_id' => $tenantId,
                'tipo_participacion' => $tipoParticipacion,
            ];
        }
        
        // Usar allParticipantes si es super admin para attach
        $relation = Auth::user()->hasRole('super_admin') ? $asamblea->allParticipantes() : $asamblea->participantes();
        $relation->attach($attachData);

        return true;
    }

    /**
     * Remover participante de la asamblea
     */
    public function removeParticipant(Asamblea $asamblea, int $participanteId): bool
    {
        // Usar allParticipantes si es super admin para detach
        $relation = Auth::user()->hasRole('super_admin') ? $asamblea->allParticipantes() : $asamblea->participantes();
        $relation->detach($participanteId);

        return true;
    }

    /**
     * Actualizar tipo de participación o registrar asistencia
     */
    public function updateParticipant(Asamblea $asamblea, int $participanteId, ?string $tipoParticipacion = null, ?bool $asistio = null): bool
    {
        $updateData = [];
        
        if ($tipoParticipacion !== null) {
            $updateData['tipo_participacion'] = $tipoParticipacion;
        }
        
        if ($asistio !== null) {
            $updateData['asistio'] = $asistio;
            if ($asistio) {
                $updateData['hora_registro'] = now();
            }
            $updateData['updated_by'] = Auth::user()->id;  // Admin que registra
        }

        // Usar allParticipantes si es super admin para updateExistingPivot
        $relation = Auth::user()->hasRole('super_admin') ? $asamblea->allParticipantes() : $asamblea->participantes();
        $relation->updateExistingPivot($participanteId, $updateData);

        return true;
    }

    /**
     * Determinar la relación correcta según el rol del usuario
     */
    private function getParticipantsRelation(Asamblea $asamblea)
    {
        return Auth::user()->hasRole('super_admin') ? $asamblea->allParticipantes() : $asamblea->participantes();
    }
}