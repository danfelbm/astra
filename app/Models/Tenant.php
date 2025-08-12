<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'subdomain',
        'settings',
        'active',
        'subscription_plan',
        'limits'
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'limits' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Valores por defecto para atributos
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'active' => true,
        'subscription_plan' => 'basic',
        'settings' => '{}',
        'limits' => '{}'
    ];

    /**
     * Obtener todos los usuarios del tenant
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Obtener todas las votaciones del tenant
     */
    public function votaciones()
    {
        return $this->hasMany(Votacion::class);
    }

    /**
     * Obtener todas las convocatorias del tenant
     */
    public function convocatorias()
    {
        return $this->hasMany(Convocatoria::class);
    }

    /**
     * Verificar si el tenant está activo
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Obtener configuración específica
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Establecer configuración específica
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings;
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /**
     * Verificar límite específico
     */
    public function checkLimit(string $resource): bool
    {
        $limit = data_get($this->limits, "max_{$resource}");
        
        if (is_null($limit)) {
            return true; // Sin límite
        }

        $currentCount = match($resource) {
            'users' => $this->users()->count(),
            'votaciones' => $this->votaciones()->count(),
            'convocatorias' => $this->convocatorias()->count(),
            default => 0
        };

        return $currentCount < $limit;
    }

    /**
     * Scope para tenants activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para buscar por subdomain
     */
    public function scopeBySubdomain($query, string $subdomain)
    {
        return $query->where('subdomain', $subdomain);
    }
}