<?php

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracion extends Model
{
    use HasTenant;
    protected $table = 'configuraciones';

    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'descripcion',
        'categoria',
        'publico',
        'validacion',
    ];

    protected $casts = [
        'publico' => 'boolean',
        'validacion' => 'array',
    ];

    /**
     * Boot del modelo para limpiar cache cuando se modifica
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('configuraciones_cache');
        });

        static::deleted(function () {
            Cache::forget('configuraciones_cache');
        });
    }

    /**
     * Obtener valor de configuración con casting automático
     */
    public function getValorCasteadoAttribute()
    {
        return $this->castearValor($this->valor, $this->tipo);
    }

    /**
     * Castear valor según su tipo
     */
    protected function castearValor($valor, $tipo)
    {
        if ($valor === null) {
            return null;
        }

        return match ($tipo) {
            'boolean' => filter_var($valor, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $valor,
            'float' => (float) $valor,
            'json' => json_decode($valor, true),
            'file', 'string' => (string) $valor,
            default => $valor,
        };
    }

    /**
     * Métodos estáticos para gestión de configuraciones
     */

    /**
     * Obtener valor de configuración
     */
    public static function obtener(string $clave, $default = null)
    {
        $configuraciones = self::obtenerTodas();
        
        if (!isset($configuraciones[$clave])) {
            return $default;
        }

        $config = $configuraciones[$clave];
        return (new self)->castearValor($config['valor'], $config['tipo']) ?? $default;
    }

    /**
     * Establecer valor de configuración
     */
    public static function establecer(string $clave, $valor, string $tipo = 'string', array $opciones = [])
    {
        // Convertir valor a string para almacenar
        $valorString = match ($tipo) {
            'boolean' => $valor ? '1' : '0',
            'json' => json_encode($valor),
            default => (string) $valor,
        };

        self::updateOrCreate(
            ['clave' => $clave],
            array_merge([
                'valor' => $valorString,
                'tipo' => $tipo,
            ], $opciones)
        );

        return true;
    }

    /**
     * Obtener todas las configuraciones (con cache)
     */
    public static function obtenerTodas(): array
    {
        return Cache::remember('configuraciones_cache', 3600, function () {
            return self::all()->keyBy('clave')->map(function ($config) {
                return [
                    'valor' => $config->valor,
                    'tipo' => $config->tipo,
                    'publico' => $config->publico,
                    'categoria' => $config->categoria,
                    'descripcion' => $config->descripcion,
                ];
            })->toArray();
        });
    }

    /**
     * Obtener configuraciones públicas (para frontend)
     */
    public static function obtenerPublicas(): array
    {
        $configuraciones = self::obtenerTodas();
        
        return collect($configuraciones)
            ->filter(fn($config) => $config['publico'])
            ->map(fn($config, $clave) => (new self)->castearValor($config['valor'], $config['tipo']))
            ->toArray();
    }

    /**
     * Obtener configuraciones por categoría
     */
    public static function obtenerPorCategoria(string $categoria): array
    {
        $configuraciones = self::obtenerTodas();
        
        return collect($configuraciones)
            ->filter(fn($config) => $config['categoria'] === $categoria)
            ->map(fn($config, $clave) => (new self)->castearValor($config['valor'], $config['tipo']))
            ->toArray();
    }

    /**
     * Limpiar cache de configuraciones
     */
    public static function limpiarCache(): void
    {
        Cache::forget('configuraciones_cache');
    }

}