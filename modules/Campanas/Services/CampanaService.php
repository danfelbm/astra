<?php

namespace Modules\Campanas\Services;

use Modules\Campanas\Models\Campana;
use Modules\Campanas\Models\CampanaEnvio;
use Modules\Campanas\Repositories\CampanaRepository;
use Modules\Campanas\Jobs\ProcessCampanaBatchJob;
use Modules\Core\Services\TenantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampanaService
{
    public function __construct(
        private CampanaRepository $repository,
        private TenantService $tenantService
    ) {}

    /**
     * Crear una nueva campaña
     */
    public function create(array $data): array
    {
        DB::beginTransaction();
        try {
            // Agregar tenant_id si existe
            $tenantId = $this->tenantService->getCurrentTenant()?->id;
            if ($tenantId) {
                $data['tenant_id'] = $tenantId;
            }
            
            // Configuración por defecto
            $configuracion = [
                'batch_size_email' => $data['batch_size_email'] ?? config('campanas.batch.email.size', 50),
                'batch_size_whatsapp' => $data['batch_size_whatsapp'] ?? config('campanas.batch.whatsapp.size', 20),
                'whatsapp_min_delay' => $data['whatsapp_min_delay'] ?? config('campanas.batch.whatsapp.min_delay', 3),
                'whatsapp_max_delay' => $data['whatsapp_max_delay'] ?? config('campanas.batch.whatsapp.max_delay', 8),
                'tracking_enabled' => $data['tracking_enabled'] ?? true,
            ];
            
            $data['configuracion'] = $configuracion;
            
            // Crear campaña
            $campana = $this->repository->create($data);
            
            // Crear registro de métricas iniciales
            // Esto es importante para que muestre el conteo de destinatarios antes de iniciar
            if ($campana->segment_id) {
                $totalDestinatarios = $campana->contarDestinatarios();
                
                \Modules\Campanas\Models\CampanaMetrica::create([
                    'campana_id' => $campana->id,
                    'tenant_id' => $campana->tenant_id,
                    'total_destinatarios' => $totalDestinatarios,
                    'total_pendientes' => 0, // Aún no hay envíos creados
                    'total_enviados' => 0,
                    'total_fallidos' => 0,
                ]);
                
                Log::info('Métricas iniciales creadas para campaña', [
                    'campana_id' => $campana->id,
                    'total_destinatarios' => $totalDestinatarios
                ]);
            }
            
            // Si está programada, registrar para envío
            if ($campana->estado === 'programada' && $campana->fecha_programada) {
                // Aquí se podría agregar lógica para programar el job
                Log::info('Campaña programada para: ' . $campana->fecha_programada);
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'campana' => $campana,
                'message' => 'Campaña creada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creando campaña', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al crear la campaña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar campaña
     */
    public function update(Campana $campana, array $data): array
    {
        // Verificar si se puede actualizar
        if (!in_array($campana->estado, ['borrador', 'programada', 'pausada'])) {
            return [
                'success' => false,
                'message' => 'Solo se pueden editar campañas en estado borrador, programada o pausada'
            ];
        }
        
        DB::beginTransaction();
        try {
            // Actualizar configuración si se proporciona
            if (isset($data['batch_size_email']) || isset($data['batch_size_whatsapp'])) {
                $configuracion = $campana->configuracion ?? [];
                
                if (isset($data['batch_size_email'])) {
                    $configuracion['batch_size_email'] = $data['batch_size_email'];
                }
                if (isset($data['batch_size_whatsapp'])) {
                    $configuracion['batch_size_whatsapp'] = $data['batch_size_whatsapp'];
                }
                if (isset($data['whatsapp_min_delay'])) {
                    $configuracion['whatsapp_min_delay'] = $data['whatsapp_min_delay'];
                }
                if (isset($data['whatsapp_max_delay'])) {
                    $configuracion['whatsapp_max_delay'] = $data['whatsapp_max_delay'];
                }
                
                $data['configuracion'] = $configuracion;
            }
            
            // Actualizar campaña
            $this->repository->update($campana, $data);
            
            // Si se cambió el segmento, actualizar métricas
            if (isset($data['segment_id']) && $data['segment_id'] != $campana->segment_id) {
                $campana->refresh(); // Recargar con nuevo segment_id
                $totalDestinatarios = $campana->contarDestinatarios();
                
                if ($campana->metrica) {
                    $campana->metrica->update([
                        'total_destinatarios' => $totalDestinatarios
                    ]);
                } else {
                    // Crear métricas si no existen
                    \Modules\Campanas\Models\CampanaMetrica::create([
                        'campana_id' => $campana->id,
                        'tenant_id' => $campana->tenant_id,
                        'total_destinatarios' => $totalDestinatarios,
                        'total_pendientes' => 0,
                        'total_enviados' => 0,
                        'total_fallidos' => 0,
                    ]);
                }
                
                Log::info('Métricas actualizadas por cambio de segmento', [
                    'campana_id' => $campana->id,
                    'total_destinatarios' => $totalDestinatarios
                ]);
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'campana' => $campana->fresh(),
                'message' => 'Campaña actualizada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error actualizando campaña', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id,
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al actualizar la campaña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar campaña
     */
    public function delete(Campana $campana): array
    {
        // Verificar si se puede eliminar
        if ($campana->estado !== 'borrador') {
            return [
                'success' => false,
                'message' => 'Solo se pueden eliminar campañas en estado borrador'
            ];
        }
        
        DB::beginTransaction();
        try {
            $this->repository->delete($campana);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Campaña eliminada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error eliminando campaña', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al eliminar la campaña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Iniciar envío de campaña
     */
    public function iniciarEnvio(Campana $campana): array
    {
        // Verificar si puede enviarse
        $validacion = $campana->puedeEnviarse();
        if (!$validacion['puede_enviarse']) {
            return [
                'success' => false,
                'message' => 'La campaña no puede enviarse',
                'errors' => $validacion['errores']
            ];
        }
        
        DB::beginTransaction();
        try {
            // Marcar inicio
            $this->repository->marcarInicio($campana);
            
            // Crear registros de envío para cada destinatario
            $destinatarios = $campana->getDestinatarios();
            $enviosCreados = 0;
            
            foreach ($destinatarios as $user) {
                // Crear envío de email si aplica
                if ($campana->usaEmail() && !empty($user->email)) {
                    CampanaEnvio::create([
                        'tenant_id' => $campana->tenant_id,
                        'campana_id' => $campana->id,
                        'user_id' => $user->id,
                        'tipo' => 'email',
                        'destinatario' => $user->email,
                    ]);
                    $enviosCreados++;
                }
                
                // Crear envío de WhatsApp si aplica
                if ($campana->usaWhatsApp() && !empty($user->telefono)) {
                    CampanaEnvio::create([
                        'tenant_id' => $campana->tenant_id,
                        'campana_id' => $campana->id,
                        'user_id' => $user->id,
                        'tipo' => 'whatsapp',
                        'destinatario' => $user->telefono,
                    ]);
                    $enviosCreados++;
                }
            }
            
            // Crear o actualizar métricas iniciales
            if (!$campana->metrica) {
                \Modules\Campanas\Models\CampanaMetrica::create([
                    'campana_id' => $campana->id,
                    'tenant_id' => $campana->tenant_id,
                    'total_destinatarios' => $destinatarios->count(),
                    'total_pendientes' => $enviosCreados,
                ]);
            }
            
            // Disparar job de procesamiento
            ProcessCampanaBatchJob::dispatch($campana);
            
            DB::commit();
            
            Log::info('Campaña iniciada', [
                'campana_id' => $campana->id,
                'envios_creados' => $enviosCreados,
                'destinatarios' => $destinatarios->count()
            ]);
            
            return [
                'success' => true,
                'message' => "Campaña iniciada. Se enviarán {$enviosCreados} mensajes a {$destinatarios->count()} destinatarios",
                'stats' => [
                    'envios_creados' => $enviosCreados,
                    'destinatarios' => $destinatarios->count()
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error iniciando campaña', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al iniciar la campaña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Pausar campaña
     */
    public function pausar(Campana $campana): array
    {
        DB::beginTransaction();
        try {
            if (!$this->repository->pausar($campana)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'La campaña no puede pausarse en su estado actual'
                ];
            }
            
            DB::commit();
            
            Log::info('Campaña pausada', ['campana_id' => $campana->id]);
            
            return [
                'success' => true,
                'message' => 'Campaña pausada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error pausando campaña', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al pausar la campaña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reanudar campaña
     */
    public function reanudar(Campana $campana): array
    {
        DB::beginTransaction();
        try {
            if (!$this->repository->reanudar($campana)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'La campaña no puede reanudarse en su estado actual'
                ];
            }
            
            // Disparar job de procesamiento nuevamente
            ProcessCampanaBatchJob::dispatch($campana);
            
            DB::commit();
            
            Log::info('Campaña reanudada', ['campana_id' => $campana->id]);
            
            return [
                'success' => true,
                'message' => 'Campaña reanudada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error reanudando campaña', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al reanudar la campaña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancelar campaña
     */
    public function cancelar(Campana $campana): array
    {
        DB::beginTransaction();
        try {
            if (!$this->repository->cancelar($campana)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'La campaña no puede cancelarse en su estado actual'
                ];
            }
            
            // Marcar todos los envíos pendientes como cancelados
            $campana->envios()
                ->where('estado', 'pendiente')
                ->update(['estado' => 'fallido', 'error_mensaje' => 'Campaña cancelada']);
            
            DB::commit();
            
            Log::info('Campaña cancelada', ['campana_id' => $campana->id]);
            
            return [
                'success' => true,
                'message' => 'Campaña cancelada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error cancelando campaña', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al cancelar la campaña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Duplicar campaña
     */
    public function duplicar(Campana $campana): array
    {
        DB::beginTransaction();
        try {
            $nueva = $this->repository->duplicar($campana);
            
            DB::commit();
            
            return [
                'success' => true,
                'campana' => $nueva,
                'message' => 'Campaña duplicada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error duplicando campaña', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al duplicar la campaña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Previsualizar campaña
     */
    public function preview(Campana $campana, int $userId = null): array
    {
        try {
            // Obtener usuario de ejemplo
            $user = null;
            if ($userId) {
                $user = \Modules\Core\Models\User::find($userId);
            } else {
                // Obtener primer usuario del segmento
                $destinatarios = $campana->getDestinatarios();
                $user = $destinatarios->first();
            }
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener un usuario para la previsualización'
                ];
            }
            
            $preview = [
                'usuario' => [
                    'nombre' => $user->name,
                    'email' => $user->email,
                    'telefono' => $user->telefono,
                ],
                'destinatarios_total' => $campana->contarDestinatarios(),
            ];
            
            // Previsualizar email si aplica
            if ($campana->usaEmail() && $campana->plantillaEmail) {
                $preview['email'] = [
                    'asunto' => $campana->plantillaEmail->procesarAsunto($user),
                    'contenido' => $campana->plantillaEmail->procesarContenido($user),
                ];
            }
            
            // Previsualizar WhatsApp si aplica
            if ($campana->usaWhatsApp() && $campana->plantillaWhatsApp) {
                $preview['whatsapp'] = [
                    'contenido' => $campana->plantillaWhatsApp->procesarContenido($user),
                ];
            }
            
            return [
                'success' => true,
                'preview' => $preview
            ];
        } catch (\Exception $e) {
            Log::error('Error previsualizando campaña', [
                'error' => $e->getMessage(),
                'campana_id' => $campana->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al previsualizar la campaña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar campañas programadas
     */
    public function procesarProgramadas(): void
    {
        $campanas = $this->repository->getProgramadasParaIniciar();
        
        foreach ($campanas as $campana) {
            Log::info('Procesando campaña programada', ['campana_id' => $campana->id]);
            $this->iniciarEnvio($campana);
        }
    }

    /**
     * Actualizar métricas de campaña
     */
    public function actualizarMetricas(Campana $campana): void
    {
        if ($campana->metrica) {
            $campana->metrica->actualizarMetricas();
        } else {
            // Crear métricas si no existen
            \Modules\Campanas\Models\CampanaMetrica::create([
                'campana_id' => $campana->id,
                'tenant_id' => $campana->tenant_id,
            ])->actualizarMetricas();
        }
    }
}