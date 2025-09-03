<?php

namespace Modules\Core\Http\Controllers\Api;

use Modules\Core\Http\Controllers\Base\Controller;
use Modules\Core\Services\QueueRateLimiterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueStatusController extends Controller
{
    protected QueueRateLimiterService $rateLimiter;
    
    /**
     * Constructor
     */
    public function __construct(QueueRateLimiterService $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }
    
    /**
     * Obtener estado general de las colas OTP
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $stats = $this->rateLimiter->getQueueStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Estimar tiempo de envío para un nuevo OTP
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function estimate(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:email,whatsapp',
        ]);
        
        $estimate = $this->rateLimiter->estimateWaitTime($request->type);
        
        return response()->json([
            'success' => true,
            'data' => $estimate,
            'message' => $estimate['estimated_seconds'] > 60 
                ? "Tu OTP será enviado en aproximadamente {$estimate['estimated_time']}. Te encuentras en la posición {$estimate['position']} de la cola."
                : "Tu OTP será enviado en los próximos segundos.",
        ]);
    }
    
    /**
     * Obtener posición en cola para un email o teléfono específico
     *
     * @param Request $request
     * @param string $identifier
     * @return JsonResponse
     */
    public function position(Request $request, string $identifier): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:email,whatsapp',
        ]);
        
        $position = $this->rateLimiter->getQueuePosition($request->type, $identifier);
        
        if (!$position) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró tu solicitud en la cola. Es posible que ya haya sido procesada.',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $position,
            'message' => $position['total_ahead'] > 0
                ? "Tu OTP está en la posición {$position['position']} de la cola. Tiempo estimado: {$position['estimated_time']}."
                : "Tu OTP está siendo procesado ahora mismo.",
        ]);
    }
    
    /**
     * Obtener métricas de las últimas 24 horas
     *
     * @return JsonResponse
     */
    public function metrics(): JsonResponse
    {
        // Solo permitir a administradores ver las métricas
        if (!auth()->user() || !auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }
        
        $metrics = $this->rateLimiter->getMetrics();
        
        return response()->json([
            'success' => true,
            'data' => $metrics,
            'summary' => [
                'email' => [
                    'total_sent' => collect($metrics['email'])->sum('sent'),
                    'total_throttled' => collect($metrics['email'])->sum('throttled'),
                    'average_success_rate' => collect($metrics['email'])->avg('success_rate'),
                ],
                'whatsapp' => [
                    'total_sent' => collect($metrics['whatsapp'])->sum('sent'),
                    'total_throttled' => collect($metrics['whatsapp'])->sum('throttled'),
                    'average_success_rate' => collect($metrics['whatsapp'])->avg('success_rate'),
                ],
            ],
        ]);
    }
}