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
            case 'App\Jobs\SendOTPEmailJob':
            case 'App\Jobs\SendZoomAccessEmailJob':
            case 'App\Jobs\SendCandidaturaReminderEmailJob':
            case 'App\Jobs\SendCandidaturaPendienteEmailJob':
            case 'App\Jobs\SendCandidaturaAprobadaEmailJob':
            case 'App\Jobs\SendCandidaturaRechazadaEmailJob':
            case 'App\Jobs\SendCandidaturaBorradorEmailJob':
                $middlewares[] = RateLimited::forEmail();
                break;
                
            case 'App\Jobs\SendOTPWhatsAppJob':
            case 'App\Jobs\SendZoomAccessWhatsAppJob':
            case 'App\Jobs\SendCandidaturaReminderWhatsAppJob':
            case 'App\Jobs\SendCandidaturaPendienteWhatsAppJob':
            case 'App\Jobs\SendCandidaturaAprobadaWhatsAppJob':
            case 'App\Jobs\SendCandidaturaRechazadaWhatsAppJob':
            case 'App\Jobs\SendCandidaturaBorradorWhatsAppJob':
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
            case 'App\Jobs\SendOTPEmailJob':
            case 'App\Jobs\SendZoomAccessEmailJob':
            case 'App\Jobs\SendCandidaturaReminderEmailJob':
            case 'App\Jobs\SendCandidaturaPendienteEmailJob':
            case 'App\Jobs\SendCandidaturaAprobadaEmailJob':
            case 'App\Jobs\SendCandidaturaRechazadaEmailJob':
            case 'App\Jobs\SendCandidaturaBorradorEmailJob':
                $this->onQueue(config('queue.otp_email_queue', 'otp-emails'));
                break;
                
            case 'App\Jobs\SendOTPWhatsAppJob':
            case 'App\Jobs\SendZoomAccessWhatsAppJob':
            case 'App\Jobs\SendCandidaturaReminderWhatsAppJob':
            case 'App\Jobs\SendCandidaturaPendienteWhatsAppJob':
            case 'App\Jobs\SendCandidaturaAprobadaWhatsAppJob':
            case 'App\Jobs\SendCandidaturaRechazadaWhatsAppJob':
            case 'App\Jobs\SendCandidaturaBorradorWhatsAppJob':
                $this->onQueue(config('queue.otp_whatsapp_queue', 'otp-whatsapp'));
                break;
                
            default:
                // Mantener la cola por defecto si no hay configuración específica
                break;
        }
    }
}