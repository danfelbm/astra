<?php

namespace Modules\Asamblea\Http\Controllers\User;

use Modules\Core\Http\Controllers\Base\UserController;


use Modules\Asamblea\Models\ZoomRegistrant;
use Modules\Asamblea\Models\ZoomRegistrantAccess;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Controlador para redirección pública de enlaces Zoom enmascarados
 */
class ZoomRedirectController extends UserController
{
    /**
     * Redireccionar desde enlace enmascarado a zoom_join_url real
     * 
     * GET /videoconferencia/{masked_id}
     */
    public function redirect(Request $request, string $maskedId): RedirectResponse
    {
        try {
            // 1. Desencriptar el ID del ZoomRegistrant
            $zoomRegistrantId = $this->decryptMaskedId($maskedId);
            
            // 2. Obtener el registro con validaciones
            $zoomRegistrant = $this->getValidZoomRegistrant($zoomRegistrantId);
            
            // 3. Validar timing de la asamblea
            $this->validateAssemblyTiming($zoomRegistrant);
            
            // 4. Registrar acceso de forma asíncrona (no bloqueante)
            $this->recordAccess($zoomRegistrant, $request);
            
            // 5. Redirección inmediata al enlace real de Zoom
            return redirect($zoomRegistrant->zoom_join_url, 302, [
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (DecryptException $e) {
            Log::warning('Enlace Zoom inválido - Error de desencriptación', [
                'masked_id' => $maskedId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'error' => $e->getMessage()
            ]);
            
            return redirect('/')->with('error', 'Enlace de videoconferencia inválido o expirado.');
            
        } catch (\Exception $e) {
            Log::error('Error en redirección Zoom', [
                'masked_id' => $maskedId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Redirección amigable en caso de error
            return redirect('/')->with('error', $e->getMessage());
        }
    }

    /**
     * Desencriptar el ID enmascarado
     */
    private function decryptMaskedId(string $maskedId): int
    {
        try {
            $decrypted = Crypt::decrypt($maskedId);
            
            // Verificar que sea un número válido
            if (!is_numeric($decrypted) || $decrypted <= 0) {
                throw new \Exception('ID de registro inválido.');
            }
            
            return (int) $decrypted;
            
        } catch (DecryptException $e) {
            throw new \Exception('Enlace de videoconferencia inválido o expirado.');
        }
    }

    /**
     * Obtener y validar el ZoomRegistrant
     */
    private function getValidZoomRegistrant(int $zoomRegistrantId): ZoomRegistrant
    {
        $zoomRegistrant = ZoomRegistrant::with(['asamblea', 'user'])
            ->find($zoomRegistrantId);

        if (!$zoomRegistrant) {
            throw new \Exception('Registro de videoconferencia no encontrado.');
        }

        if (!$zoomRegistrant->zoom_join_url) {
            throw new \Exception('Enlace de videoconferencia no disponible.');
        }

        if (!$zoomRegistrant->asamblea) {
            throw new \Exception('Asamblea asociada no encontrada.');
        }

        // Verificar que la asamblea esté activa
        if (!$zoomRegistrant->asamblea->activo) {
            throw new \Exception('La asamblea ya no está activa.');
        }

        // Verificar que la asamblea no esté cancelada
        if ($zoomRegistrant->asamblea->estado === 'cancelada') {
            throw new \Exception('La asamblea ha sido cancelada.');
        }

        return $zoomRegistrant;
    }

    /**
     * Validar timing de la asamblea
     */
    private function validateAssemblyTiming(ZoomRegistrant $zoomRegistrant): void
    {
        $asamblea = $zoomRegistrant->asamblea;

        // Usar la lógica específica del modelo Asamblea que maneja tanto API como SDK
        if (!$asamblea->zoomDisponibleParaUnirse()) {
            // Mensaje específico según el tipo de integración
            if ($asamblea->zoom_integration_type === 'api' && $asamblea->zoom_registration_open_date) {
                $now = now();
                if ($now < $asamblea->zoom_registration_open_date) {
                    $minutesUntilOpen = $now->diffInMinutes($asamblea->zoom_registration_open_date);
                    throw new \Exception("La videoconferencia estará disponible en {$minutesUntilOpen} minutos (cuando abran las inscripciones).");
                } else {
                    throw new \Exception('La videoconferencia ya no está disponible. La asamblea ha finalizado.');
                }
            } else {
                // Para SDK o API sin fecha específica
                $now = now();
                $earliestAccess = $asamblea->fecha_inicio->copy()->subMinutes(15);
                if ($now < $earliestAccess) {
                    $minutesUntilAccess = $now->diffInMinutes($earliestAccess);
                    throw new \Exception("La videoconferencia estará disponible en {$minutesUntilAccess} minutos (15 minutos antes del inicio de la asamblea).");
                } else {
                    throw new \Exception('La videoconferencia ya no está disponible. La asamblea ha finalizado.');
                }
            }
        }

        // Log para debugging en production
        Log::info('Acceso a videoconferencia autorizado', [
            'asamblea_id' => $asamblea->id,
            'zoom_registrant_id' => $zoomRegistrant->id,
            'user_id' => $zoomRegistrant->user_id,
            'estado_asamblea' => $asamblea->estado,
            'timing_valid' => true
        ]);
    }

    /**
     * Registrar acceso de forma asíncrona para no bloquear la redirección
     */
    private function recordAccess(ZoomRegistrant $zoomRegistrant, Request $request): void
    {
        try {
            // Extraer datos primitivos para evitar problemas de serialización
            $zoomRegistrantId = $zoomRegistrant->id;
            $userAgent = $request->userAgent();
            $ipAddress = $request->ip();
            $maskedUrl = $request->url(); // Capturar URL completa del enlace enmascarado
            $referer = $request->header('referer'); // Capturar referer si existe
            
            // Usar dispatch con datos primitivos para hacer esto asíncrono y no bloquear la redirección
            dispatch(function () use ($zoomRegistrantId, $userAgent, $ipAddress, $maskedUrl, $referer) {
                ZoomRegistrantAccess::createAccess(
                    $zoomRegistrantId,
                    $userAgent,
                    $ipAddress,
                    $maskedUrl,
                    $referer
                );
            })->afterResponse();

        } catch (\Exception $e) {
            // No propagar errores de tracking para no afectar la redirección
            Log::warning('Error registrando acceso a Zoom (no crítico)', [
                'zoom_registrant_id' => $zoomRegistrant->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Método para generar enlace enmascarado (usado por otros servicios)
     */
    public static function generateMaskedUrl(int $zoomRegistrantId): string
    {
        $encryptedId = Crypt::encrypt($zoomRegistrantId);
        return route('zoom.redirect', ['masked_id' => $encryptedId]);
    }

    /**
     * Método para verificar si un enlace es válido sin redireccionar
     */
    public function verify(Request $request, string $maskedId)
    {
        try {
            $zoomRegistrantId = $this->decryptMaskedId($maskedId);
            $zoomRegistrant = $this->getValidZoomRegistrant($zoomRegistrantId);
            $this->validateAssemblyTiming($zoomRegistrant);
            
            return response()->json([
                'valid' => true,
                'asamblea' => [
                    'id' => $zoomRegistrant->asamblea->id,
                    'nombre' => $zoomRegistrant->asamblea->nombre,
                    'estado' => $zoomRegistrant->asamblea->estado,
                    'estado_label' => $zoomRegistrant->asamblea->getEstadoLabel(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
