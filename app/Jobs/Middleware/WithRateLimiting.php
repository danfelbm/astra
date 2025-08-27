<?php

namespace App\Jobs\Middleware;

trait WithRateLimiting
{
    /**
     * Obtener el middleware que debe pasar el job
     *
     * @return array
     */
    public function middleware(): array
    {
        $middlewares = [];
        
        // Determinar qué tipo de rate limiting aplicar basado en el job
        $jobClass = get_class($this);
        
        switch ($jobClass) {
            case 'App\Jobs\Core\SendOTPEmailJob':
            case 'App\Jobs\Asamblea\SendZoomAccessEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaReminderEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaPendienteEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaAprobadaEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaRechazadaEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaBorradorEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaComentarioEmailJob':
                $middlewares[] = RateLimited::forEmail();
                break;
                
            case 'App\Jobs\Core\SendOTPWhatsAppJob':
            case 'App\Jobs\Asamblea\SendZoomAccessWhatsAppJob':
            case 'App\Jobs\Elecciones\SendCandidaturaReminderWhatsAppJob':
            case 'App\Jobs\Elecciones\SendCandidaturaPendienteWhatsAppJob':
            case 'App\Jobs\Elecciones\SendCandidaturaAprobadaWhatsAppJob':
            case 'App\Jobs\Elecciones\SendCandidaturaRechazadaWhatsAppJob':
            case 'App\Jobs\Elecciones\SendCandidaturaBorradorWhatsAppJob':
                $middlewares[] = RateLimited::forWhatsApp();
                break;
                
            default:
                // Si el job tiene un método getRateLimitConfig, usarlo
                if (method_exists($this, 'getRateLimitConfig')) {
                    $config = $this->getRateLimitConfig();
                    $middlewares[] = RateLimited::for(
                        $config['key'] ?? 'default',
                        $config['limit'] ?? 10,
                        $config['decay'] ?? 1
                    );
                }
                break;
        }
        
        return $middlewares;
    }
    
    /**
     * Inicializar la cola para jobs con rate limiting
     * Este método debe llamarse en el constructor del job
     *
     * @return void
     */
    protected function initializeRateLimitedQueue(): void
    {
        $jobClass = get_class($this);
        
        // Asignar colas dedicadas según el tipo de job
        switch ($jobClass) {
            case 'App\Jobs\Core\SendOTPEmailJob':
            case 'App\Jobs\Asamblea\SendZoomAccessEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaReminderEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaPendienteEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaAprobadaEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaRechazadaEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaBorradorEmailJob':
            case 'App\Jobs\Elecciones\SendCandidaturaComentarioEmailJob':
                $this->onQueue(config('queue.otp_email_queue', 'otp-emails'));
                break;
                
            case 'App\Jobs\Core\SendOTPWhatsAppJob':
            case 'App\Jobs\Asamblea\SendZoomAccessWhatsAppJob':
            case 'App\Jobs\Elecciones\SendCandidaturaReminderWhatsAppJob':
            case 'App\Jobs\Elecciones\SendCandidaturaPendienteWhatsAppJob':
            case 'App\Jobs\Elecciones\SendCandidaturaAprobadaWhatsAppJob':
            case 'App\Jobs\Elecciones\SendCandidaturaRechazadaWhatsAppJob':
            case 'App\Jobs\Elecciones\SendCandidaturaBorradorWhatsAppJob':
                $this->onQueue(config('queue.otp_whatsapp_queue', 'otp-whatsapp'));
                break;
                
            default:
                // Mantener la cola por defecto si no hay configuración específica
                break;
        }
    }
}