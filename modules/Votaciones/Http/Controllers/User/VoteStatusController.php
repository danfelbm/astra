<?php

namespace Modules\Votaciones\Http\Controllers\User;

use Modules\Core\Http\Controllers\UserController;
use Modules\Votaciones\Models\Votacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class VoteStatusController extends UserController
{
    /**
     * Verificar el estado del procesamiento de un voto.
     * Este endpoint será consultado por el frontend mediante polling.
     */
    public function check(Votacion $votacion): JsonResponse
    {
        $user = auth()->user();
        $cacheKey = "vote_status_{$votacion->id}_{$user->id}";
        
        // Obtener estado del cache
        $status = Cache::get($cacheKey, 'pending');
        
        // Si el voto fue completado, verificar que realmente existe en BD
        if ($status === 'completed') {
            $votoExists = $votacion->votos()
                ->where('usuario_id', $user->id)
                ->exists();
                
            if (!$votoExists) {
                // Si no existe en BD, seguir esperando
                $status = 'processing';
            }
        }
        
        // Construir respuesta según el estado
        $response = [
            'status' => $status,
            'message' => $this->getStatusMessage($status),
            'completed' => $status === 'completed',
            'error' => in_array($status, ['error', 'failed', 'duplicate']),
        ];
        
        // Si está completado, incluir información adicional
        if ($status === 'completed') {
            $voto = $votacion->votos()
                ->where('usuario_id', $user->id)
                ->first();
                
            if ($voto) {
                $response['voto_id'] = $voto->id;
                $response['token_preview'] = substr($voto->token_unico, 0, 8) . '...';
                $response['created_at'] = $voto->created_at->toISOString();
            }
        }
        
        return response()->json($response);
    }
    
    /**
     * Obtener mensaje amigable según el estado.
     */
    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Preparando firma digital...',
            'processing' => 'Firmando digitalmente tu voto, por favor espera...',
            'completed' => '¡Tu voto ha sido firmado y registrado exitosamente!',
            'duplicate' => 'Ya has votado en esta votación.',
            'error' => 'Hubo un error al firmar tu voto. Por favor, intenta nuevamente.',
            'failed' => 'No se pudo firmar tu voto. Por favor, contacta al administrador.',
            default => 'Verificando firma digital...'
        };
    }
}