<?php

namespace Modules\Votaciones\Http\Controllers\Guest;

use Modules\Core\Http\Controllers\GuestController;


use Modules\Votaciones\Models\Votacion;
use Modules\Votaciones\Services\TokenService;
use Modules\Votaciones\Services\CryptoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TokenVerificationController extends GuestController
{
    /**
     * Página pública para verificar tokens
     */
    public function index(): Response
    {
        return Inertia::render('Modules/Votaciones/Guest/VerificarToken');
    }

    /**
     * Verificar un token específico (página web)
     */
    public function show(string $token): Response
    {
        $verification = $this->verifyTokenInternal($token);
        
        return Inertia::render('Modules/Votaciones/Guest/VerificarToken', [
            'token' => $token,
            'verification' => $verification,
        ]);
    }

    /**
     * API endpoint para verificar tokens
     */
    public function api(string $token): JsonResponse
    {
        $verification = $this->verifyTokenInternal($token);
        
        return response()->json($verification);
    }

    /**
     * Obtener la clave pública del servidor
     */
    public function publicKey(): JsonResponse
    {
        try {
            $publicKey = CryptoService::getPublicKey();
            
            return response()->json([
                'public_key' => $publicKey,
                'format' => 'PEM',
                'algorithm' => 'RSA-2048',
                'signature_algorithm' => 'SHA256withRSA'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Clave pública no disponible',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lógica interna de verificación de tokens
     */
    private function verifyTokenInternal(string $token): array
    {
        // Verificar formato básico
        if (!TokenService::isValidTokenFormat($token)) {
            return [
                'is_valid' => false,
                'error' => 'Formato de token inválido',
                'token' => $token,
                'vote_data' => null,
                'votacion' => null,
                'verification_details' => [
                    'format_valid' => false,
                    'signature_valid' => false,
                    'hash_valid' => false,
                    'votacion_exists' => false,
                ]
            ];
        }

        // Intentar verificar como token firmado (nuevo formato)
        $tokenVerification = TokenService::verifyToken($token);
        
        if (!$tokenVerification['is_valid']) {
            
            // Si no es válido como token firmado, podría ser un token antiguo
            return [
                'is_valid' => false,
                'error' => $tokenVerification['error'] ?: 'Token inválido o corrupto',
                'token' => $token,
                'vote_data' => null,
                'votacion' => null,
                'verification_details' => [
                    'format_valid' => true,
                    'signature_valid' => $tokenVerification['signature_valid'],
                    'hash_valid' => $tokenVerification['hash_valid'],
                    'votacion_exists' => false,
                ]
            ];
        }

        $voteData = $tokenVerification['vote_data'];
        
        // Buscar información de la votación
        $votacion = null;
        $votacionExists = false;
        
        if (isset($voteData['votacion_id'])) {
            $votacion = Votacion::with('categoria')->find($voteData['votacion_id']);
            $votacionExists = $votacion !== null;
        }

        return [
            'is_valid' => true,
            'error' => null,
            'token' => $token,
            'vote_data' => [
                'votacion_id' => $voteData['votacion_id'],
                'respuestas' => $voteData['respuestas'],
                'timestamp' => $voteData['vote_created_at'] ?? $voteData['timestamp'] ?? null, // Compatibilidad con tokens antiguos
                'vote_created_at' => $voteData['vote_created_at'] ?? $voteData['timestamp'] ?? null,
                'urna_opened_at' => $voteData['urna_opened_at'] ?? null,
                'tiempo_en_urna' => $voteData['tiempo_en_urna'] ?? null,
                'vote_hash' => $voteData['vote_hash'],
            ],
            'votacion' => $votacion ? [
                'id' => $votacion->id,
                'titulo' => $votacion->titulo,
                'descripcion' => $votacion->descripcion,
                'categoria' => $votacion->categoria->nombre,
                'formulario_config' => $votacion->formulario_config,
                'fecha_inicio' => $votacion->fecha_inicio->toISOString(),
                'fecha_fin' => $votacion->fecha_fin->toISOString(),
                'estado' => $votacion->estado,
            ] : null,
            'verification_details' => [
                'format_valid' => true,
                'signature_valid' => $tokenVerification['signature_valid'],
                'hash_valid' => $tokenVerification['hash_valid'],
                'votacion_exists' => $votacionExists,
                'verified_at' => now()->toISOString(),
            ]
        ];
    }
}