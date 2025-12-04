<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $instance;
    protected bool $enabled;
    protected string $mode;

    public function __construct()
    {
        $this->apiKey = config('services.whatsapp.api_key', '');
        $this->baseUrl = rtrim(config('services.whatsapp.base_url', ''), '/');
        $this->instance = config('services.whatsapp.instance', '');
        $this->enabled = config('services.whatsapp.enabled', false);
        $this->mode = config('services.whatsapp.mode', 'production');
    }

    /**
     * Verificar si WhatsApp está habilitado
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->apiKey) && !empty($this->baseUrl) && !empty($this->instance);
    }

    /**
     * Verificar si está en modo LOG
     */
    public function isLogMode(): bool
    {
        return $this->mode === 'log';
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

        // Formatear número de teléfono y validar
        $formattedPhone = $this->formatPhoneNumber($phone);
        
        if (!$this->validatePhoneNumber($formattedPhone)) {
            Log::error("Número de teléfono inválido: {$phone}");
            return false;
        }

        // Si está en modo LOG, solo registrar en log
        if ($this->isLogMode()) {
            return $this->logMessage($formattedPhone, $message);
        }

        try {
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
                Log::error("Error enviando WhatsApp", [
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
     * Registrar mensaje en modo LOG
     */
    private function logMessage(string $phone, string $message): bool
    {
        // Obtener tipo de mensaje basado en el contenido
        $messageType = $this->detectMessageType($message);
        
        // Crear preview del mensaje sin información sensible
        $preview = $this->createMessagePreview($message, $messageType);

        Log::info('[WHATSAPP MODE LOG] Mensaje enviado satisfactoriamente', [
            'mode' => 'log',
            'to' => $phone,
            'type' => $messageType,
            'preview' => $preview,
            'timestamp' => now()->toISOString(),
        ]);

        return true;
    }

    /**
     * Detectar tipo de mensaje basado en el contenido
     */
    private function detectMessageType(string $message): string
    {
        if (str_contains($message, 'código de verificación')) {
            return 'OTP';
        } elseif (str_contains($message, 'Confirmación de Voto')) {
            return 'VOTE_CONFIRMATION';
        } elseif (str_contains($message, 'Zoom')) {
            return 'ZOOM_ACCESS';
        } elseif (str_contains($message, 'candidatura')) {
            return 'CANDIDATURA_UPDATE';
        } else {
            return 'GENERAL';
        }
    }

    /**
     * Crear preview del mensaje ocultando información sensible
     */
    private function createMessagePreview(string $message, string $type): string
    {
        switch ($type) {
            case 'OTP':
                // Ocultar el código OTP
                return preg_replace('/\*([0-9]{4,6})\*/', '******', $message);
            
            case 'VOTE_CONFIRMATION':
                // Ocultar el token
                $preview = preg_replace('/```([^`]+)```/', '```[TOKEN_OCULTO]```', $message);
                return substr($preview, 0, 150) . '...';
            
            default:
                // Para otros tipos, mostrar solo los primeros caracteres
                return substr($message, 0, 100) . '...';
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

        // En modo LOG, simular conexión exitosa
        if ($this->isLogMode()) {
            Log::info('[WHATSAPP MODE LOG] Conexión simulada exitosa');
            return true;
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
     * Enviar mensaje (método usado por campañas)
     * 
     * @param string $telefono Número de teléfono
     * @param string $mensaje Mensaje a enviar
     * @param array $metadata Metadata adicional (campana_id, envio_id, etc)
     * @return array Respuesta con success, message_id, status
     */
    public function enviarMensaje(string $telefono, string $mensaje, array $metadata = []): array
    {
        try {
            // Si no está habilitado, retornar error
            if (!$this->isEnabled() && $this->mode !== 'log') {
                return [
                    'success' => false,
                    'message' => 'WhatsApp service is not enabled or properly configured',
                    'status' => 'disabled'
                ];
            }

            // Si está en modo LOG, registrar y retornar éxito
            if ($this->isLogMode()) {
                Log::info('[WHATSAPP CAMPAÑA - MODE LOG] Mensaje enviado satisfactoriamente', [
                    'mode' => 'log',
                    'to' => $telefono,
                    'preview' => substr($mensaje, 0, 100) . '...',
                    'metadata' => $metadata,
                    'timestamp' => now()->toISOString(),
                ]);

                return [
                    'success' => true,
                    'message_id' => 'LOG_' . uniqid(),
                    'status' => 'sent_log',
                    'message' => 'Mensaje registrado en modo LOG'
                ];
            }

            // En modo producción, usar el método sendMessage existente
            $result = $this->sendMessage($telefono, $mensaje);
            
            return [
                'success' => $result,
                'message_id' => $result ? uniqid() : null,
                'status' => $result ? 'sent' : 'failed',
                'message' => $result ? 'Mensaje enviado exitosamente' : 'Error al enviar mensaje'
            ];

        } catch (\Exception $e) {
            Log::error('Error en enviarMensaje', [
                'telefono' => $telefono,
                'error' => $e->getMessage(),
                'metadata' => $metadata
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 'error'
            ];
        }
    }

    /**
     * Obtener estadísticas del servicio
     */
    public function getStats(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'mode' => $this->mode,
            'instance' => $this->instance,
            'base_url' => $this->baseUrl,
            'connection_test' => $this->isEnabled() ? $this->testConnection() : false,
            'is_log_mode' => $this->isLogMode(),
        ];
    }

    /**
     * Enviar mensaje a un grupo de WhatsApp
     *
     * @param string $groupJid JID del grupo (ej: 120363295648424210@g.us)
     * @param string $mensaje Mensaje a enviar
     * @param array $metadata Metadata adicional (campana_id, envio_id, group_nombre, etc)
     * @return array Respuesta con success, message_id, status
     */
    public function sendGroupMessage(string $groupJid, string $mensaje, array $metadata = []): array
    {
        try {
            // Si no está habilitado, retornar error
            if (!$this->isEnabled() && $this->mode !== 'log') {
                return [
                    'success' => false,
                    'message' => 'WhatsApp service is not enabled or properly configured',
                    'status' => 'disabled'
                ];
            }

            // Si está en modo LOG, registrar y retornar éxito
            if ($this->isLogMode()) {
                Log::info('[WHATSAPP GRUPO - MODE LOG] Mensaje a grupo enviado satisfactoriamente', [
                    'mode' => 'log',
                    'group_jid' => $groupJid,
                    'group_nombre' => $metadata['group_nombre'] ?? 'N/A',
                    'preview' => substr($mensaje, 0, 100) . '...',
                    'metadata' => $metadata,
                    'timestamp' => now()->toISOString(),
                ]);

                return [
                    'success' => true,
                    'message_id' => 'LOG_GROUP_' . uniqid(),
                    'status' => 'sent_log',
                    'message' => 'Mensaje a grupo registrado en modo LOG'
                ];
            }

            // Construir URL del endpoint (mismo que para individuales)
            $url = $this->buildApiUrl('/message/sendText/' . $this->instance);

            // Preparar payload - para grupos se usa el JID completo
            $payload = [
                'number' => $groupJid,
                'text' => $mensaje,
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

                Log::info('[WHATSAPP GRUPO] Mensaje a grupo enviado', [
                    'group_jid' => $groupJid,
                    'group_nombre' => $metadata['group_nombre'] ?? 'N/A',
                    'response' => $responseData,
                ]);

                return [
                    'success' => true,
                    'message_id' => $responseData['key']['id'] ?? uniqid(),
                    'status' => 'sent',
                    'message' => 'Mensaje a grupo enviado exitosamente'
                ];
            } else {
                Log::error('[WHATSAPP GRUPO] Error enviando mensaje a grupo', [
                    'group_jid' => $groupJid,
                    'group_nombre' => $metadata['group_nombre'] ?? 'N/A',
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message_id' => null,
                    'status' => 'failed',
                    'message' => 'Error al enviar mensaje a grupo: ' . $response->body()
                ];
            }

        } catch (\Exception $e) {
            Log::error('[WHATSAPP GRUPO] Excepción enviando mensaje a grupo', [
                'group_jid' => $groupJid,
                'error' => $e->getMessage(),
                'metadata' => $metadata
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 'error'
            ];
        }
    }
}