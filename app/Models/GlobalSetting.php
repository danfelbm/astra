<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GlobalSetting extends Model
{
    /**
     * Nota: Este modelo NO usa HasTenant porque son configuraciones globales
     * que aplican a todo el sistema, no a un tenant específico.
     */
    
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'category',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Boot del modelo para limpiar cache cuando se modifica
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('global_settings_cache');
        });

        static::deleted(function () {
            Cache::forget('global_settings_cache');
        });
    }

    /**
     * Obtener valor casteado según su tipo
     */
    public function getValueCastedAttribute()
    {
        return $this->castValue($this->value, $this->type);
    }

    /**
     * Castear valor según su tipo
     */
    protected function castValue($value, $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            'enum', 'string' => (string) $value,
            default => $value,
        };
    }

    /**
     * Obtener valor de configuración global
     */
    public static function get(string $key, $default = null)
    {
        $settings = self::getAllCached();
        
        if (!isset($settings[$key])) {
            return $default;
        }

        $setting = $settings[$key];
        return (new self)->castValue($setting['value'], $setting['type']) ?? $default;
    }

    /**
     * Establecer valor de configuración global
     */
    public static function set(string $key, $value, string $type = 'string', array $options = [])
    {
        // Convertir valor a string para almacenar
        $valueString = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        // Si es enum, validar que el valor esté en las opciones permitidas
        if ($type === 'enum' && isset($options['allowed_values'])) {
            if (!in_array($value, $options['allowed_values'])) {
                throw new \InvalidArgumentException("Valor '{$value}' no permitido para enum. Valores permitidos: " . implode(', ', $options['allowed_values']));
            }
        }

        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $valueString,
                'type' => $type,
                'description' => $options['description'] ?? null,
                'category' => $options['category'] ?? null,
                'options' => $options,
            ]
        );

        return true;
    }

    /**
     * Obtener todas las configuraciones globales (con cache)
     */
    public static function getAllCached(): array
    {
        return Cache::remember('global_settings_cache', 3600, function () {
            return self::all()->keyBy('key')->map(function ($setting) {
                return [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'category' => $setting->category,
                    'description' => $setting->description,
                    'options' => $setting->options,
                ];
            })->toArray();
        });
    }

    /**
     * Obtener configuraciones por categoría
     */
    public static function getByCategory(string $category): array
    {
        $settings = self::getAllCached();
        
        return collect($settings)
            ->filter(fn($setting) => $setting['category'] === $category)
            ->map(fn($setting, $key) => (new self)->castValue($setting['value'], $setting['type']))
            ->toArray();
    }

    /**
     * Limpiar cache de configuraciones globales
     */
    public static function clearCache(): void
    {
        Cache::forget('global_settings_cache');
    }
}
