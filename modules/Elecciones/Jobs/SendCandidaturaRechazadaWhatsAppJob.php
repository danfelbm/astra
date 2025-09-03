<?php

namespace Modules\Elecciones\Jobs;

use Modules\Core\Jobs\Middleware\WithRateLimiting;
use Modules\Core\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCandidaturaRechazadaWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WithRateLimiting;

    /**
     * El número de veces que el job puede ser intentado.
     * IMPORTANTE: Este límite se respeta SIEMPRE por Laravel
     *
     * @var int
     */
    public $tries = 3;

    /**
     * El número de segundos para esperar antes de reintentar el job.
     *
     * @var int
     */
    public $backoff = 10;
    
    /**
     * El tiempo máximo que el job puede ejecutarse antes de timeout
     *
     * @var int
     */
    public $timeout = 30;
    
    /**
     * Determinar si el job debería marcarse como fallido en caso de excepción
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $phone,
        public string $userName,
        public int $candidaturaId,
        public string $comentarios
    ) {
        // Inicializar la cola dedicada para WhatsApp
        $this->initializeRateLimitedQueue();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Verificar si el número es obviamente inválido antes de intentar
            if (!$this->isPhoneFormatValid($this->phone)) {
                Log::warning("Número de teléfono con formato inválido, no se enviará notificación de candidatura rechazada", [
                    'phone' => $this->phone,
                    'candidatura_id' => $this->candidaturaId,
                    'length' => strlen($this->phone)
                ]);
                
                // No reintentar para números con formato inválido
                $this->fail(new \Exception("Formato de número de teléfono inválido: {$this->phone}"));
                return;
            }
            
            // Crear instancia del servicio WhatsApp
            $whatsappService = new WhatsAppService();
            
            // Generar mensaje con plantilla
            $message = $this->getCandidaturaRechazadaMessageTemplate($this->userName, $this->comentarios);
            
            // Enviar mensaje por WhatsApp
            $sent = $whatsappService->sendMessage($this->phone, $message);
            
            if ($sent) {
                Log::info("Notificación de candidatura rechazada enviada exitosamente por WhatsApp", [
                    'phone' => $this->phone,
                    'candidatura_id' => $this->candidaturaId
                ]);
            } else {
                // El servicio retornó false - verificar si es error permanente
                $this->handleSendFailure();
            }
            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Manejar falla en el envío
     */
    protected function handleSendFailure(): void
    {
        Log::warning("WhatsApp service retornó false para notificación de candidatura rechazada", [
            'phone' => $this->phone,
            'candidatura_id' => $this->candidaturaId,
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries
        ]);
        
        // Si es un error que podría ser temporal, re-intentar
        throw new \Exception("Fallo al enviar WhatsApp de candidatura rechazada a {$this->phone}");
    }

    /**
     * Manejar excepciones
     */
    protected function handleException(\Exception $e): void
    {
        // Determinar si es un error permanente
        if ($this->isPermanentError($e)) {
            Log::error("Error permanente enviando notificación de candidatura rechazada por WhatsApp", [
                'phone' => $this->phone,
                'candidatura_id' => $this->candidaturaId,
                'error' => $e->getMessage()
            ]);
            
            // Fallar el job sin reintentos
            $this->fail($e);
        } else {
            Log::warning("Error temporal enviando notificación de candidatura rechazada por WhatsApp, se reintentará", [
                'phone' => $this->phone,
                'candidatura_id' => $this->candidaturaId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries
            ]);
            
            // Re-lanzar para reintento
            throw $e;
        }
    }

    /**
     * Verificar si el formato del teléfono es válido
     */
    protected function isPhoneFormatValid(string $phone): bool
    {
        // Eliminar espacios y caracteres especiales
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Verificar longitud mínima (al menos 8 dígitos)
        if (strlen($cleanPhone) < 8) {
            return false;
        }
        
        // Verificar longitud máxima (no más de 15 dígitos)
        if (strlen($cleanPhone) > 15) {
            return false;
        }
        
        return true;
    }

    /**
     * Determinar si es un error permanente
     */
    protected function isPermanentError(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        
        // Errores que indican problemas permanentes
        $permanentErrors = [
            'invalid number',
            'número inválido',
            'blocked',
            'bloqueado',
            'not registered',
            'no registrado',
            'invalid format',
            'formato inválido'
        ];
        
        foreach ($permanentErrors as $error) {
            if (str_contains($message, $error)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de notificación de candidatura rechazada por WhatsApp falló definitivamente", [
            'phone' => $this->phone,
            'candidatura_id' => $this->candidaturaId,
            'error' => $exception->getMessage(),
            'total_attempts' => $this->tries
        ]);
    }

    /**
     * Obtener plantilla de mensaje para candidatura rechazada
     * Convierte HTML a texto plano para WhatsApp
     */
    protected function getCandidaturaRechazadaMessageTemplate(string $userName, string $comentariosHtml): string
    {
        $platformName = config('app.name', 'Sistema de Votaciones');
        $platformUrl = config('app.url');
        
        // Convertir comentarios HTML a texto plano
        $comentariosTexto = $this->htmlToWhatsApp($comentariosHtml);
        
        return "📝 *Hola {$userName}*\n\n" .
               "Tu candidatura requiere algunos ajustes para ser aprobada.\n\n" .
               "❌ Estado: *Rechazada*\n\n" .
               "📌 *Comentarios de la comisión:*\n" .
               "{$comentariosTexto}\n\n" .
               "Por favor, ingresa a la plataforma para realizar las correcciones necesarias:\n" .
               "{$platformUrl}/candidaturas\n\n" .
               "_Este es un mensaje automático de {$platformName}_";
    }

    /**
     * Convertir HTML a formato de WhatsApp
     */
    protected function htmlToWhatsApp(string $html): string
    {
        // Eliminar tags HTML pero mantener saltos de línea
        $text = str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $html);
        
        // Convertir listas a formato WhatsApp
        $text = preg_replace('/<li[^>]*>/i', '• ', $text);
        
        // Eliminar todos los tags HTML restantes
        $text = strip_tags($text);
        
        // Limpiar espacios múltiples
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        $text = preg_replace('/ +/', ' ', $text);
        
        // Trim y retornar
        return trim($text);
    }
}