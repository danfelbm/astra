<?php

namespace Modules\Asamblea\Services;

use Modules\Asamblea\Models\Asamblea;
use Modules\Votaciones\Models\Votacion;
use Modules\Asamblea\Jobs\SyncParticipantsToVotacionJob;
use Modules\Asamblea\Services\ZoomService;
use Modules\Core\Services\TenantService;

class AsambleaService
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * Crear una nueva asamblea con todas sus dependencias
     */
    public function create(array $data): array
    {
        $asamblea = Asamblea::create($data);

        // Sincronizar votaciones asociadas si se proporcionaron
        if (!empty($data['votacion_ids'])) {
            $this->syncVotacionesToAsamblea($asamblea, $data['votacion_ids']);
        }

        // Crear reunión de Zoom solo si está habilitado y es modo SDK
        $zoomResult = null;
        if (($data['zoom_enabled'] ?? false) && ($data['zoom_integration_type'] ?? 'sdk') === 'sdk') {
            $zoomResult = $this->createZoomMeeting($asamblea);
        }

        return [
            'success' => true,
            'asamblea' => $asamblea,
            'zoom_result' => $zoomResult,
            'message' => $this->getCreateSuccessMessage($data, $zoomResult)
        ];
    }

    /**
     * Sincronizar votaciones a una asamblea
     */
    private function syncVotacionesToAsamblea(Asamblea $asamblea, array $votacionIds): void
    {
        $tenantId = $this->tenantService->getCurrentTenant()?->id;
        $syncData = [];
        
        foreach ($votacionIds as $votacionId) {
            $syncData[$votacionId] = ['tenant_id' => $tenantId];
        }
        
        $asamblea->votaciones()->sync($syncData);
    }

    /**
     * Crear reunión de Zoom para la asamblea
     */
    private function createZoomMeeting(Asamblea $asamblea): array
    {
        $zoomService = new ZoomService();
        $result = $zoomService->createMeeting($asamblea);
        
        if (!$result['success']) {
            // Si falla la creación de Zoom, deshabilitar pero no fallar la creación de la asamblea
            $asamblea->update(['zoom_enabled' => false]);
        }
        
        return $result;
    }

    /**
     * Obtener mensaje de éxito para creación
     */
    private function getCreateSuccessMessage(array $data, ?array $zoomResult): string
    {
        $baseMessage = 'Asamblea creada exitosamente.';
        
        if ($zoomResult && !$zoomResult['success']) {
            return $baseMessage . ' Pero no se pudo crear la reunión de Zoom: ' . $zoomResult['message'];
        }
        
        if ($data['zoom_enabled'] ?? false) {
            return $baseMessage . ' Reunión de Zoom configurada.';
        }
        
        return $baseMessage;
    }

    /**
     * Actualizar una asamblea con todas sus dependencias
     */
    public function update(Asamblea $asamblea, array $data): array
    {
        // Validar cambio de estado a 'en_curso'
        if ($data['estado'] === 'en_curso' && $asamblea->participantes()->count() === 0) {
            return [
                'success' => false,
                'message' => 'No se puede iniciar una asamblea sin participantes asignados.',
                'field' => 'estado'
            ];
        }

        // Manejar cambios en Zoom
        $zoomMessages = $this->handleZoomUpdates($asamblea, $data);

        // Actualizar la asamblea
        $asamblea->update($data);

        // Sincronizar votaciones asociadas si se proporcionaron
        if (isset($data['votacion_ids'])) {
            $this->syncVotacionesToAsamblea($asamblea, $data['votacion_ids']);
        }

        $message = 'Asamblea actualizada exitosamente.';
        if (!empty($zoomMessages)) {
            $message .= ' ' . implode(' ', $zoomMessages);
        }

        return [
            'success' => true,
            'asamblea' => $asamblea,
            'message' => $message
        ];
    }

    /**
     * Manejar actualizaciones de Zoom
     */
    private function handleZoomUpdates(Asamblea $asamblea, array &$data): array
    {
        $zoomMessages = [];
        $currentIntegrationType = $data['zoom_integration_type'] ?? $asamblea->zoom_integration_type ?? 'sdk';
        $previousIntegrationType = $asamblea->zoom_integration_type ?? 'sdk';
        
        // Solo gestionar Zoom automáticamente si es modo SDK
        if ($currentIntegrationType === 'sdk') {
            $zoomService = new ZoomService();
            
            // Si se está habilitando Zoom por primera vez
            if ($data['zoom_enabled'] && !$asamblea->zoom_enabled) {
                $result = $zoomService->createMeeting($asamblea);
                if (!$result['success']) {
                    $data['zoom_enabled'] = false;
                    $zoomMessages[] = 'No se pudo crear la reunión de Zoom: ' . $result['message'];
                } else {
                    $zoomMessages[] = 'Reunión de Zoom creada exitosamente.';
                }
            }
            // Si se está deshabilitando Zoom
            elseif (!$data['zoom_enabled'] && $asamblea->zoom_enabled) {
                $result = $zoomService->deleteMeeting($asamblea);
                if ($result['success']) {
                    $zoomMessages[] = 'Reunión de Zoom eliminada.';
                }
            }
            // Si ya tiene Zoom habilitado y se está actualizando
            elseif ($data['zoom_enabled'] && $asamblea->zoom_enabled && $previousIntegrationType === 'sdk') {
                $result = $zoomService->updateMeeting($asamblea);
                if (!$result['success']) {
                    $zoomMessages[] = 'No se pudo actualizar la reunión de Zoom: ' . $result['message'];
                } else {
                    $zoomMessages[] = 'Reunión de Zoom actualizada.';
                }
            }
        }
        // Si se cambió de SDK a API, eliminar la reunión automática de Zoom
        elseif ($currentIntegrationType === 'api' && $previousIntegrationType === 'sdk' && $asamblea->zoom_enabled) {
            $zoomService = new ZoomService();
            $result = $zoomService->deleteMeeting($asamblea);
            if ($result['success']) {
                $zoomMessages[] = 'Reunión automática de Zoom eliminada. Ahora usa configuración manual.';
            }
        }

        return $zoomMessages;
    }

    /**
     * Eliminar una asamblea con todas las validaciones de negocio
     */
    public function delete(Asamblea $asamblea): array
    {
        // Verificar que no esté en curso
        if ($asamblea->estado === 'en_curso') {
            return [
                'success' => false,
                'message' => 'No se puede eliminar una asamblea en curso.',
                'field' => 'delete'
            ];
        }

        // Verificar que no tenga acta registrada
        if ($asamblea->acta_url) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar una asamblea con acta registrada.',
                'field' => 'delete'
            ];
        }

        // Verificar que no tenga votaciones asociadas
        if ($asamblea->votaciones()->exists()) {
            $cantidadVotaciones = $asamblea->votaciones()->count();
            return [
                'success' => false,
                'message' => "No se puede eliminar una asamblea con {$cantidadVotaciones} votación(es) asociada(s). Primero debe desvincular las votaciones.",
                'field' => 'delete'
            ];
        }

        // Eliminar la asamblea
        $asamblea->delete();

        return [
            'success' => true,
            'message' => 'Asamblea eliminada exitosamente.'
        ];
    }

    /**
     * Sincronizar participantes de una asamblea a una votación
     */
    public function syncParticipantsToVotacion(Asamblea $asamblea, Votacion $votacion): array
    {
        // Verificar que la votación esté asociada a la asamblea
        if (!$this->isVotacionAssociatedToAsamblea($asamblea, $votacion)) {
            return [
                'success' => false,
                'message' => 'La votación no está asociada a esta asamblea',
                'status' => 400
            ];
        }

        // Verificar que la asamblea tenga participantes
        if (!$this->asambleaHasParticipants($asamblea)) {
            return [
                'success' => false,
                'message' => 'La asamblea no tiene participantes para sincronizar',
                'status' => 400
            ];
        }

        // Despachar el job de sincronización
        $job = new SyncParticipantsToVotacionJob($asamblea, $votacion);
        dispatch($job);

        return [
            'success' => true,
            'message' => 'Sincronización iniciada',
            'job_id' => $job->getJobId(),
            'status' => 200
        ];
    }

    /**
     * Verificar si una votación está asociada a una asamblea
     */
    private function isVotacionAssociatedToAsamblea(Asamblea $asamblea, Votacion $votacion): bool
    {
        return $asamblea->votaciones()->where('votaciones.id', $votacion->id)->exists();
    }

    /**
     * Verificar si una asamblea tiene participantes
     */
    private function asambleaHasParticipants(Asamblea $asamblea): bool
    {
        return $asamblea->participantes()->count() > 0;
    }

    /**
     * Obtener el estado de un job de sincronización
     */
    public function getSyncJobStatus(string $jobId): array
    {
        $cacheKey = "sync_job_{$jobId}";
        $status = \Cache::get($cacheKey);

        if (!$status) {
            return [
                'success' => false,
                'message' => 'Job no encontrado o expirado',
                'status' => 404
            ];
        }

        return [
            'success' => true,
            'data' => $status,
            'status' => 200
        ];
    }
}