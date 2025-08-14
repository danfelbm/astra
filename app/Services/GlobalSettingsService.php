<?php

namespace App\Services;

use App\Enums\LoginType;
use App\Models\GlobalSetting;

class GlobalSettingsService
{
    /**
     * Obtener el tipo de login configurado globalmente
     */
    public static function getLoginType(): LoginType
    {
        $value = GlobalSetting::get('auth.login_type', LoginType::EMAIL->value);
        
        // Validar que el valor sea válido
        try {
            return LoginType::from($value);
        } catch (\ValueError $e) {
            // Si el valor no es válido, retornar el por defecto
            return LoginType::EMAIL;
        }
    }

    /**
     * Establecer el tipo de login global
     */
    public static function setLoginType(LoginType $loginType): bool
    {
        return GlobalSetting::set(
            'auth.login_type',
            $loginType->value,
            'enum',
            [
                'category' => 'auth',
                'description' => 'Tipo de login del sistema (email o documento)',
                'allowed_values' => LoginType::values(),
            ]
        );
    }

    /**
     * Obtener configuración de autenticación para el frontend
     */
    public static function getAuthConfig(): array
    {
        $loginType = self::getLoginType();
        
        return [
            'login_type' => $loginType->value,
            'input_type' => $loginType->inputType(),
            'placeholder' => $loginType->placeholder(),
            'pattern' => $loginType->pattern(),
            'label' => $loginType->label(),
        ];
    }

    /**
     * Inicializar configuraciones globales por defecto
     */
    public static function initializeDefaults(): void
    {
        // Configuración de tipo de login
        if (!GlobalSetting::where('key', 'auth.login_type')->exists()) {
            self::setLoginType(LoginType::EMAIL);
        }

        // Otras configuraciones globales futuras pueden añadirse aquí
        $defaultSettings = [
            [
                'key' => 'system.maintenance_mode',
                'value' => false,
                'type' => 'boolean',
                'options' => [
                    'category' => 'system',
                    'description' => 'Modo de mantenimiento del sistema',
                ],
            ],
            [
                'key' => 'system.allow_registration',
                'value' => false,
                'type' => 'boolean',
                'options' => [
                    'category' => 'system',
                    'description' => 'Permitir auto-registro de usuarios',
                ],
            ],
        ];

        foreach ($defaultSettings as $setting) {
            if (!GlobalSetting::where('key', $setting['key'])->exists()) {
                GlobalSetting::set(
                    $setting['key'],
                    $setting['value'],
                    $setting['type'],
                    $setting['options']
                );
            }
        }
    }

    /**
     * Obtener todas las configuraciones globales por categoría
     */
    public static function getAllByCategory(): array
    {
        $settings = GlobalSetting::getAllCached();
        $grouped = [];

        foreach ($settings as $key => $setting) {
            $category = $setting['category'] ?? 'general';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            
            $grouped[$category][$key] = [
                'value' => GlobalSetting::get($key),
                'type' => $setting['type'],
                'description' => $setting['description'],
                'options' => $setting['options'],
            ];
        }

        return $grouped;
    }

    /**
     * Verificar si el sistema está en modo mantenimiento
     */
    public static function isMaintenanceMode(): bool
    {
        return GlobalSetting::get('system.maintenance_mode', false);
    }

    /**
     * Verificar si se permite el auto-registro
     */
    public static function isRegistrationAllowed(): bool
    {
        return GlobalSetting::get('system.allow_registration', false);
    }

    /**
     * Obtener el canal de OTP configurado
     */
    public static function getOTPChannel(): string
    {
        // Si está configurado en GlobalSettings, usar eso
        $globalChannel = GlobalSetting::get('otp.channel');
        if ($globalChannel) {
            return $globalChannel;
        }
        
        // Si no, usar el valor del archivo de configuración
        return config('services.otp.channel', 'email');
    }

    /**
     * Establecer el canal de OTP
     */
    public static function setOTPChannel(string $channel): bool
    {
        // Validar que el canal sea válido
        if (!in_array($channel, ['email', 'whatsapp', 'both'])) {
            throw new \InvalidArgumentException("Canal OTP inválido: {$channel}");
        }
        
        return GlobalSetting::set(
            'otp.channel',
            $channel,
            'enum',
            [
                'category' => 'otp',
                'description' => 'Canal de envío de códigos OTP',
                'allowed_values' => ['email', 'whatsapp', 'both'],
            ]
        );
    }

    /**
     * Verificar si WhatsApp está habilitado
     */
    public static function isWhatsAppEnabled(): bool
    {
        // Primero verificar si está configurado en GlobalSettings
        $globalEnabled = GlobalSetting::get('whatsapp.enabled');
        if ($globalEnabled !== null) {
            return $globalEnabled;
        }
        
        // Si no, usar el valor del archivo de configuración
        return config('services.whatsapp.enabled', false);
    }

    /**
     * Obtener estadísticas de OTP
     */
    public static function getOTPStats(): array
    {
        $otpService = new \App\Services\OTPService();
        return $otpService->getStats();
    }

    /**
     * Obtener configuración completa de OTP/WhatsApp
     */
    public static function getMessagingConfig(): array
    {
        return [
            'otp' => [
                'channel' => self::getOTPChannel(),
                'expiration_minutes' => config('services.otp.expiration_minutes', 10),
                'send_immediately' => config('services.otp.send_immediately', true),
            ],
            'whatsapp' => [
                'enabled' => self::isWhatsAppEnabled(),
                'instance' => config('services.whatsapp.instance'),
                'configured' => !empty(config('services.whatsapp.api_key')),
            ],
        ];
    }
}