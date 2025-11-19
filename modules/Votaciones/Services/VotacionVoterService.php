<?php

namespace Modules\Votaciones\Services;

use Modules\Votaciones\Models\Votacion;
use Modules\Core\Services\TenantService;

class VotacionVoterService
{
    public function __construct(
        private TenantService $tenantService
    ) {
    }

    /**
     * Asignar votantes a una votación
     */
    public function assignVoters(Votacion $votacion, array $votanteIds): void
    {
        // Obtener el tenant_id actual
        $tenantId = $this->tenantService->getCurrentTenant()?->id;

        // Preparar los datos para attach con tenant_id
        $attachData = [];
        foreach ($votanteIds as $votanteId) {
            $attachData[$votanteId] = ['tenant_id' => $tenantId];
        }

        $votacion->votantes()->attach($attachData);
    }

    /**
     * Remover un votante de una votación
     */
    public function removeVoter(Votacion $votacion, int $votanteId): void
    {
        $votacion->votantes()->detach($votanteId);
    }
}
