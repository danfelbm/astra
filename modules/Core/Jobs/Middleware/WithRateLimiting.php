<?php

namespace Modules\Core\Jobs\Middleware;

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
            // Jobs de Email con rate limiting
            case 'Modules\Core\Jobs\SendOTPEmailJob':
            case 'Modules\Asamblea\Jobs\SendZoomAccessEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaReminderEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaPendienteEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaAprobadaEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaRechazadaEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaBorradorEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaComentarioEmailJob':
            case 'Modules\Votaciones\Jobs\SendVoteConfirmationEmailJob':
                $middlewares[] = RateLimited::forEmail();
                break;
                
            // Jobs de WhatsApp con rate limiting
            case 'Modules\Core\Jobs\SendOTPWhatsAppJob':
            case 'Modules\Asamblea\Jobs\SendZoomAccessWhatsAppJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaReminderWhatsAppJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaPendienteWhatsAppJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaAprobadaWhatsAppJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaRechazadaWhatsAppJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaBorradorWhatsAppJob':
            case 'Modules\Votaciones\Jobs\SendVoteConfirmationWhatsAppJob':
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
            case 'Modules\Core\Jobs\SendOTPEmailJob':
            case 'Modules\Asamblea\Jobs\SendZoomAccessEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaReminderEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaPendienteEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaAprobadaEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaRechazadaEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaBorradorEmailJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaComentarioEmailJob':
            case 'Modules\Votaciones\Jobs\SendVoteConfirmationEmailJob':
                $this->onQueue(config('queue.otp_email_queue', 'otp-emails'));
                break;
                
            case 'Modules\Core\Jobs\SendOTPWhatsAppJob':
            case 'Modules\Asamblea\Jobs\SendZoomAccessWhatsAppJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaReminderWhatsAppJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaPendienteWhatsAppJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaAprobadaWhatsAppJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaRechazadaWhatsAppJob':
            case 'Modules\Elecciones\Jobs\SendCandidaturaBorradorWhatsAppJob':
            case 'Modules\Votaciones\Jobs\SendVoteConfirmationWhatsAppJob':
                $this->onQueue(config('queue.otp_whatsapp_queue', 'otp-whatsapp'));
                break;
                
            default:
                // Mantener la cola por defecto si no hay configuración específica
                break;
        }
    }
}