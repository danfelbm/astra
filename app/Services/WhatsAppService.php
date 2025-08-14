<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $instance;
    protected bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('services.whatsapp.api_key', '');
        $this->baseUrl = rtrim(config('services.whatsapp.base_url', ''), '/');
        $this->instance = config('services.whatsapp.instance', '');
        $this->enabled = config('services.whatsapp.enabled', false);
    }

    /**
     * Verificar si WhatsApp está habilitado
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->apiKey) && !empty($this->baseUrl) && !empty($this->instance);
    }

    /**
     * Enviar mensaje de texto por WhatsApp
     */
    public function sendMessage(string $phone, string $message): bool
    {
        if (!$this->isEnabled()) {
            Log::warning('WhatsApp service is not enabled or properly configured');
            return false;
        }

        try {
            // Formatear número de teléfono
            $formattedPhone = $this->formatPhoneNumber($phone);
            
            if (!$this->validatePhoneNumber($formattedPhone)) {
                Log::error("Número de teléfono inválido: {$phone}");
                return false;
            }

            // Construir URL del endpoint
            $url = $this->buildApiUrl('/message/sendText/' . $this->instance);
            
            // Preparar el payload según la documentación de Evolution API
            // Formato simplificado que funciona con la API actual
            $payload = [
                'number' => $formattedPhone,
                'text' => $message,
            ];

            // Realizar la petición HTTP
            $response = Http::timeout(10)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            // Verificar respuesta
            if ($response->successful()) {
                $responseData = $response->json();
                Log::info("WhatsApp OTP enviado exitosamente", [
                    'phone' => $formattedPhone,
                    'response' => $responseData
                ]);
                return true;
            } else {
                Log::error("Error enviando WhatsApp OTP", [
                    'phone' => $formattedPhone,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }

        } catch (Exception $e) {
            Log::error("Excepción enviando WhatsApp OTP: " . $e->getMessage(), [
                'phone' => $phone,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Formatear número de teléfono con código de país
     * Asume que si no tiene + al inicio, es un número colombiano
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Eliminar espacios, guiones y paréntesis
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        // Si ya tiene formato internacional con +, solo quitar el +
        if (str_starts_with($phone, '+')) {
            return substr($phone, 1);
        }
        
        // Si empieza con código de país sin +
        if (strlen($phone) > 10) {
            return $phone;
        }
        
        // Si es un número local colombiano (10 dígitos), agregar código de país
        if (strlen($phone) == 10 && str_starts_with($phone, '3')) {
            return '57' . $phone;
        }
        
        // Retornar como está si no coincide con ningún patrón
        return $phone;
    }

    /**
     * Validar formato de número de teléfono
     */
    public function validatePhoneNumber(string $phone): bool
    {
        // Debe ser solo números
        if (!preg_match('/^\d+$/', $phone)) {
            return false;
        }
        
        // Debe tener entre 10 y 15 dígitos (estándar internacional)
        $length = strlen($phone);
        return $length >= 10 && $length <= 15;
    }

    /**
     * Construir URL completa del API
     */
    protected function buildApiUrl(string $endpoint): string
    {
        return $this->baseUrl . '/' . ltrim($endpoint, '/');
    }

    /**
     * Verificar conexión con Evolution API
     */
    public function testConnection(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            // Intentar obtener información de la instancia
            $url = $this->buildApiUrl('/instance/fetchInstances');
            
            $response = Http::timeout(5)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                ])
                ->get($url);

            return $response->successful();
        } catch (Exception $e) {
            Log::error("Error testing WhatsApp connection: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener plantilla de mensaje OTP
     */
    public function getOTPMessageTemplate(string $code, string $userName, int $expirationMinutes): string
    {
        return "Hola {$userName},\n\n" .
               "Tu código de verificación es: *{$code}*\n\n" .
               "Este código es válido por {$expirationMinutes} minutos.\n" .
               "No compartas este código con nadie.\n\n" .
               "Si no solicitaste este código, puedes ignorar este mensaje.";
    }

    /**
     * Obtener estadísticas del servicio
     */
    public function getStats(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'instance' => $this->instance,
            'base_url' => $this->baseUrl,
            'connection_test' => $this->isEnabled() ? $this->testConnection() : false,
        ];
    }
}