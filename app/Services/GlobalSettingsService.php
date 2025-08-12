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
}