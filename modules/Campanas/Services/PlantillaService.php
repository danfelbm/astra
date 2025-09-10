<?php

namespace Modules\Campanas\Services;

use Modules\Campanas\Models\PlantillaEmail;
use Modules\Campanas\Models\PlantillaWhatsApp;
use Modules\Campanas\Repositories\PlantillaRepository;
use Modules\Core\Services\TenantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlantillaService
{
    public function __construct(
        private PlantillaRepository $repository,
        private TenantService $tenantService
    ) {}

    /**
     * Crear una nueva plantilla de email
     */
    public function createEmail(array $data): array
    {
        DB::beginTransaction();
        try {
            // Agregar tenant_id si existe
            $tenantId = $this->tenantService->getCurrentTenant()?->id;
            if ($tenantId) {
                $data['tenant_id'] = $tenantId;
            }
            
            // Crear plantilla
            $plantilla = $this->repository->createEmail($data);
            
            DB::commit();
            
            return [
                'success' => true,
                'plantilla' => $plantilla,
                'message' => 'Plantilla de email creada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creando plantilla de email', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al crear la plantilla: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crear una nueva plantilla de WhatsApp
     */
    public function createWhatsApp(array $data): array
    {
        DB::beginTransaction();
        try {
            // Agregar tenant_id si existe
            $tenantId = $this->tenantService->getCurrentTenant()?->id;
            if ($tenantId) {
                $data['tenant_id'] = $tenantId;
            }
            
            // Crear plantilla
            $plantilla = $this->repository->createWhatsApp($data);
            
            DB::commit();
            
            return [
                'success' => true,
                'plantilla' => $plantilla,
                'message' => 'Plantilla de WhatsApp creada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creando plantilla de WhatsApp', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al crear la plantilla: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar plantilla de email
     */
    public function updateEmail(PlantillaEmail $plantilla, array $data): array
    {
        DB::beginTransaction();
        try {
            // Actualizar plantilla
            $this->repository->updateEmail($plantilla, $data);
            
            DB::commit();
            
            return [
                'success' => true,
                'plantilla' => $plantilla->fresh(),
                'message' => 'Plantilla de email actualizada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error actualizando plantilla de email', [
                'error' => $e->getMessage(),
                'plantilla_id' => $plantilla->id,
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al actualizar la plantilla: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar plantilla de WhatsApp
     */
    public function updateWhatsApp(PlantillaWhatsApp $plantilla, array $data): array
    {
        DB::beginTransaction();
        try {
            // Actualizar plantilla
            $this->repository->updateWhatsApp($plantilla, $data);
            
            DB::commit();
            
            return [
                'success' => true,
                'plantilla' => $plantilla->fresh(),
                'message' => 'Plantilla de WhatsApp actualizada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error actualizando plantilla de WhatsApp', [
                'error' => $e->getMessage(),
                'plantilla_id' => $plantilla->id,
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al actualizar la plantilla: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar plantilla de email
     */
    public function deleteEmail(PlantillaEmail $plantilla): array
    {
        // Verificar si tiene campañas asociadas
        if ($plantilla->campanas()->exists()) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar la plantilla porque tiene campañas asociadas'
            ];
        }
        
        DB::beginTransaction();
        try {
            $this->repository->deleteEmail($plantilla);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Plantilla de email eliminada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error eliminando plantilla de email', [
                'error' => $e->getMessage(),
                'plantilla_id' => $plantilla->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al eliminar la plantilla: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar plantilla de WhatsApp
     */
    public function deleteWhatsApp(PlantillaWhatsApp $plantilla): array
    {
        // Verificar si tiene campañas asociadas
        if ($plantilla->campanas()->exists()) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar la plantilla porque tiene campañas asociadas'
            ];
        }
        
        DB::beginTransaction();
        try {
            $this->repository->deleteWhatsApp($plantilla);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Plantilla de WhatsApp eliminada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error eliminando plantilla de WhatsApp', [
                'error' => $e->getMessage(),
                'plantilla_id' => $plantilla->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al eliminar la plantilla: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Duplicar plantilla de email
     */
    public function duplicateEmail(PlantillaEmail $plantilla): array
    {
        DB::beginTransaction();
        try {
            $nueva = $this->repository->duplicateEmail($plantilla);
            
            DB::commit();
            
            return [
                'success' => true,
                'plantilla' => $nueva,
                'message' => 'Plantilla de email duplicada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error duplicando plantilla de email', [
                'error' => $e->getMessage(),
                'plantilla_id' => $plantilla->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al duplicar la plantilla: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Duplicar plantilla de WhatsApp
     */
    public function duplicateWhatsApp(PlantillaWhatsApp $plantilla): array
    {
        DB::beginTransaction();
        try {
            $nueva = $this->repository->duplicateWhatsApp($plantilla);
            
            DB::commit();
            
            return [
                'success' => true,
                'plantilla' => $nueva,
                'message' => 'Plantilla de WhatsApp duplicada exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error duplicando plantilla de WhatsApp', [
                'error' => $e->getMessage(),
                'plantilla_id' => $plantilla->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al duplicar la plantilla: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Previsualizar plantilla de email con datos de ejemplo
     */
    public function previewEmail(PlantillaEmail $plantilla, int $userId = null): array
    {
        try {
            // Obtener usuario de ejemplo
            $user = $userId 
                ? \Modules\Core\Models\User::find($userId)
                : \Modules\Core\Models\User::first();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener un usuario para la previsualización'
                ];
            }
            
            // Procesar contenido con variables
            $contenido = $plantilla->procesarContenido($user);
            $asunto = $plantilla->procesarAsunto($user);
            
            return [
                'success' => true,
                'preview' => [
                    'asunto' => $asunto,
                    'contenido_html' => $contenido,
                    'usuario' => [
                        'nombre' => $user->name,
                        'email' => $user->email,
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error previsualizando plantilla de email', [
                'error' => $e->getMessage(),
                'plantilla_id' => $plantilla->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al previsualizar la plantilla: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Previsualizar plantilla de WhatsApp con datos de ejemplo
     */
    public function previewWhatsApp(PlantillaWhatsApp $plantilla, int $userId = null): array
    {
        try {
            // Obtener usuario de ejemplo
            $user = $userId 
                ? \Modules\Core\Models\User::find($userId)
                : \Modules\Core\Models\User::first();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener un usuario para la previsualización'
                ];
            }
            
            // Procesar contenido con variables
            $contenido = $plantilla->procesarContenido($user);
            
            return [
                'success' => true,
                'preview' => [
                    'contenido' => $contenido,
                    'longitud' => strlen($contenido),
                    'usuario' => [
                        'nombre' => $user->name,
                        'telefono' => $user->telefono ?? 'Sin teléfono',
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error previsualizando plantilla de WhatsApp', [
                'error' => $e->getMessage(),
                'plantilla_id' => $plantilla->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al previsualizar la plantilla: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validar plantilla de email
     */
    public function validateEmail(array $data): array
    {
        $errores = [];
        
        // Validar que el contenido HTML no esté vacío
        if (empty($data['contenido_html'])) {
            $errores[] = 'El contenido HTML es requerido';
        }
        
        // Validar que el asunto no esté vacío
        if (empty($data['asunto'])) {
            $errores[] = 'El asunto es requerido';
        }
        
        // Validar variables utilizadas
        $variables = $this->extractVariables($data['contenido_html'] . ' ' . $data['asunto']);
        $variablesValidas = config('campanas.template_variables');
        
        foreach ($variables as $variable) {
            $partes = explode('.', $variable);
            if (!isset($variablesValidas[$partes[0]])) {
                $errores[] = "La variable {{$variable}} no es válida";
            }
        }
        
        return [
            'valid' => empty($errores),
            'errors' => $errores,
            'variables' => $variables
        ];
    }

    /**
     * Validar plantilla de WhatsApp
     */
    public function validateWhatsApp(array $data): array
    {
        $errores = [];
        
        // Validar que el contenido no esté vacío
        if (empty($data['contenido'])) {
            $errores[] = 'El contenido es requerido';
        }
        
        // Validar longitud del mensaje
        $longitud = strlen($data['contenido']);
        if ($longitud > 4096) {
            $errores[] = "El mensaje es muy largo ({$longitud} caracteres). Máximo permitido: 4096";
        }
        
        // Validar variables utilizadas
        $variables = $this->extractVariables($data['contenido']);
        $variablesValidas = config('campanas.template_variables');
        
        foreach ($variables as $variable) {
            $partes = explode('.', $variable);
            if (!isset($variablesValidas[$partes[0]])) {
                $errores[] = "La variable {{$variable}} no es válida";
            }
        }
        
        return [
            'valid' => empty($errores),
            'errors' => $errores,
            'variables' => $variables,
            'length' => $longitud
        ];
    }

    /**
     * Extraer variables de un texto
     */
    private function extractVariables(string $text): array
    {
        $patron = '/\{\{([^}]+)\}\}/';
        preg_match_all($patron, $text, $matches);
        
        return array_unique($matches[1] ?? []);
    }
}