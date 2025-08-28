<?php

namespace App\Services\Core;

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
            Log::error("No se pudo enviar el mensaje de WhatsApp: " . $e->getMessage());
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
     * Generar plantilla de mensaje para confirmación de voto por WhatsApp
     *
     * @param string $userName Nombre del usuario
     * @param string $votacionTitulo Título de la votación
     * @param string $token Token único del voto
     * @param \DateTime|string $voteDateTime Fecha y hora del voto
     * @param string $timezone Zona horaria de la votación (default: America/Bogota)
     * @return string El mensaje formateado
     */
    public function getVoteConfirmationTemplate(string $userName, string $votacionTitulo, string $token, $voteDateTime, string $timezone = 'America/Bogota'): string
    {
        // Convertir fecha a la zona horaria correcta
        if ($voteDateTime instanceof \DateTime) {
            $carbon = \Carbon\Carbon::instance($voteDateTime);
        } else {
            $carbon = \Carbon\Carbon::parse($voteDateTime);
        }
        
        // Aplicar la zona horaria de la votación
        $carbon->setTimezone($timezone);
        $fecha = $carbon->format('d/m/Y H:i');
        
        // Obtener abreviación de zona horaria
        $timezoneAbbr = $this->getTimezoneAbbreviation($timezone);
        
        $verificationUrl = url('/verificar-token/' . $token);
        
        $message = "🗳️ *Confirmación de Voto*\n\n";
        $message .= "Hola *{$userName}*,\n\n";
        $message .= "Tu voto en *\"{$votacionTitulo}\"* ha sido registrado exitosamente.\n\n";
        $message .= "📅 *Fecha:* {$fecha} ({$timezoneAbbr})\n";
        $message .= "🔑 *Token:*\n```{$token}```\n\n";
        $message .= "✅ *Verifica tu voto:*\n{$verificationUrl}\n\n";
        $message .= "⚠️ _Guarda este token de forma segura. Es tu comprobante único e irrepetible._\n\n";
        $message .= "_Sistema de Votación Digital_";
        
        return $message;
    }

    /**
     * Obtener abreviación de zona horaria
     */
    protected function getTimezoneAbbreviation(string $timezone): string
    {
        $abbreviations = [
            'America/Bogota' => 'GMT-5',
            'America/Mexico_City' => 'GMT-6',
            'America/Lima' => 'GMT-5',
            'America/Argentina/Buenos_Aires' => 'GMT-3',
            'America/Santiago' => 'GMT-3',
            'America/Caracas' => 'GMT-4',
            'America/La_Paz' => 'GMT-4',
            'America/Guayaquil' => 'GMT-5',
            'America/Asuncion' => 'GMT-3',
            'America/Montevideo' => 'GMT-3',
            'America/Panama' => 'GMT-5',
            'America/Costa_Rica' => 'GMT-6',
            'America/Guatemala' => 'GMT-6',
            'America/Havana' => 'GMT-5',
            'America/Santo_Domingo' => 'GMT-4',
        ];

        return $abbreviations[$timezone] ?? 'GMT';
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