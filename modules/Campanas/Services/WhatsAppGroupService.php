<?php

namespace Modules\Campanas\Services;

use Modules\Campanas\Models\WhatsAppGroup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para gestión de grupos de WhatsApp via Evolution API
 */
class WhatsAppGroupService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $instance;
    protected bool $enabled;
    protected string $mode;

    public function __construct()
    {
        $this->apiKey = config('services.whatsapp.api_key', '');
        $this->baseUrl = rtrim(config('services.whatsapp.base_url', ''), '/');
        $this->instance = config('services.whatsapp.instance', '');
        $this->enabled = config('services.whatsapp.enabled', false);
        $this->mode = config('services.whatsapp.mode', 'production');
    }

    /**
     * Verificar si el servicio está habilitado
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->apiKey) && !empty($this->baseUrl) && !empty($this->instance);
    }

    /**
     * Verificar si está en modo LOG
     */
    public function isLogMode(): bool
    {
        return $this->mode === 'log';
    }

    /**
     * Sincronizar todos los grupos desde Evolution API
     * NOTA: La sincronización siempre usa la API real (modo LOG solo aplica a envíos)
     *
     * @return array{success: bool, message: string, count: int, grupos: array}
     */
    public function syncAllGroups(): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'WhatsApp no está habilitado o configurado correctamente',
                'count' => 0,
                'grupos' => [],
            ];
        }

        try {
            $url = $this->buildApiUrl("/group/fetchAllGroups/{$this->instance}");

            $response = Http::timeout(120) // Puede tardar si hay muchos grupos
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get($url, ['getParticipants' => 'false']);

            if (!$response->successful()) {
                Log::error('Error sincronizando grupos de WhatsApp', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Error al obtener grupos: ' . $response->status(),
                    'count' => 0,
                    'grupos' => [],
                ];
            }

            $gruposApi = $response->json();

            if (!is_array($gruposApi)) {
                return [
                    'success' => false,
                    'message' => 'Respuesta inválida de la API',
                    'count' => 0,
                    'grupos' => [],
                ];
            }

            $gruposActualizados = [];

            foreach ($gruposApi as $grupoData) {
                try {
                    $grupo = WhatsAppGroup::upsertFromApi($grupoData);
                    $gruposActualizados[] = $grupo;
                } catch (Exception $e) {
                    Log::warning('Error procesando grupo', [
                        'grupo' => $grupoData['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Grupos de WhatsApp sincronizados', [
                'total_api' => count($gruposApi),
                'actualizados' => count($gruposActualizados),
            ]);

            return [
                'success' => true,
                'message' => 'Sincronización completada',
                'count' => count($gruposActualizados),
                'grupos' => $gruposActualizados,
            ];

        } catch (Exception $e) {
            Log::error('Error en sincronización de grupos', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'count' => 0,
                'grupos' => [],
            ];
        }
    }

    /**
     * Buscar y añadir un grupo por JID
     *
     * @param string $groupJid El JID del grupo (ej: "120363295648424210@g.us")
     * @return array{success: bool, message: string, grupo: ?WhatsAppGroup}
     */
    public function findAndAddByJid(string $groupJid): array
    {
        // Validar formato del JID
        if (!str_ends_with($groupJid, '@g.us')) {
            return [
                'success' => false,
                'message' => 'El JID debe terminar en @g.us',
                'grupo' => null,
            ];
        }

        // Verificar si ya existe
        $existente = WhatsAppGroup::where('group_jid', $groupJid)->first();
        if ($existente) {
            return [
                'success' => true,
                'message' => 'El grupo ya existe en la base de datos',
                'grupo' => $existente,
            ];
        }

        // Siempre usa API real (modo LOG solo aplica a envíos)
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'WhatsApp no está habilitado o configurado correctamente',
                'grupo' => null,
            ];
        }

        try {
            $url = $this->buildApiUrl("/group/findGroupInfos/{$this->instance}");

            $response = Http::timeout(30)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get($url, ['groupJid' => $groupJid]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Grupo no encontrado o error de API: ' . $response->status(),
                    'grupo' => null,
                ];
            }

            $grupoData = $response->json();

            if (empty($grupoData) || empty($grupoData['id'])) {
                return [
                    'success' => false,
                    'message' => 'Respuesta inválida de la API',
                    'grupo' => null,
                ];
            }

            $grupo = WhatsAppGroup::upsertFromApi($grupoData);

            Log::info('Grupo de WhatsApp añadido', [
                'jid' => $groupJid,
                'nombre' => $grupo->nombre,
            ]);

            return [
                'success' => true,
                'message' => 'Grupo añadido correctamente',
                'grupo' => $grupo,
            ];

        } catch (Exception $e) {
            Log::error('Error añadiendo grupo por JID', [
                'jid' => $groupJid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'grupo' => null,
            ];
        }
    }

    /**
     * Obtener participantes de un grupo (on-demand, no se almacenan)
     * NOTA: Siempre usa la API real (modo LOG solo aplica a envíos)
     *
     * @param string $groupJid
     * @return array{success: bool, message: string, participantes: array}
     */
    public function getParticipants(string $groupJid): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'WhatsApp no está habilitado o configurado correctamente',
                'participantes' => [],
            ];
        }

        try {
            $url = $this->buildApiUrl("/group/participants/{$this->instance}");

            $response = Http::timeout(30)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get($url, ['groupJid' => $groupJid]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Error obteniendo participantes: ' . $response->status(),
                    'participantes' => [],
                ];
            }

            $data = $response->json();
            $participantes = $data['participants'] ?? $data ?? [];

            return [
                'success' => true,
                'message' => 'Participantes obtenidos',
                'participantes' => $participantes,
            ];

        } catch (Exception $e) {
            Log::error('Error obteniendo participantes', [
                'jid' => $groupJid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'participantes' => [],
            ];
        }
    }

    /**
     * Previsualizar grupo por JID (sin guardarlo)
     * NOTA: Siempre usa la API real (modo LOG solo aplica a envíos)
     *
     * @param string $groupJid
     * @return array{success: bool, message: string, preview: ?array}
     */
    public function previewByJid(string $groupJid): array
    {
        // Validar formato
        if (!str_ends_with($groupJid, '@g.us')) {
            return [
                'success' => false,
                'message' => 'El JID debe terminar en @g.us',
                'preview' => null,
            ];
        }

        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'WhatsApp no está habilitado o configurado correctamente',
                'preview' => null,
            ];
        }

        try {
            $url = $this->buildApiUrl("/group/findGroupInfos/{$this->instance}");

            $response = Http::timeout(30)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get($url, ['groupJid' => $groupJid]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Grupo no encontrado: ' . $response->status(),
                    'preview' => null,
                ];
            }

            $data = $response->json();

            if (empty($data) || empty($data['id'])) {
                return [
                    'success' => false,
                    'message' => 'Grupo no encontrado',
                    'preview' => null,
                ];
            }

            return [
                'success' => true,
                'message' => 'Grupo encontrado',
                'preview' => $data,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'preview' => null,
            ];
        }
    }

    /**
     * Actualizar datos de un grupo desde la API
     */
    public function refreshGroup(WhatsAppGroup $grupo): array
    {
        $result = $this->previewByJid($grupo->group_jid);

        if (!$result['success']) {
            return $result;
        }

        $grupoActualizado = WhatsAppGroup::upsertFromApi($result['preview']);

        return [
            'success' => true,
            'message' => 'Grupo actualizado',
            'grupo' => $grupoActualizado,
        ];
    }

    /**
     * Construir URL de la API
     */
    protected function buildApiUrl(string $endpoint): string
    {
        return $this->baseUrl . '/' . ltrim($endpoint, '/');
    }
}
