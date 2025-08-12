<?php

namespace App\Services;

use App\Models\Configuracion;
use Illuminate\Support\Facades\Storage;

class ConfiguracionService
{
    /**
     * Obtener valor de configuración
     */
    public static function obtener(string $clave, $default = null)
    {
        return Configuracion::obtener($clave, $default);
    }

    /**
     * Establecer configuración simple
     */
    public static function establecer(string $clave, $valor, string $tipo = 'string', array $opciones = [])
    {
        return Configuracion::establecer($clave, $valor, $tipo, $opciones);
    }

    /**
     * Configurar múltiples valores de una vez
     */
    public static function configurarVarios(array $configuraciones): bool
    {
        foreach ($configuraciones as $config) {
            if (!isset($config['clave'], $config['valor'])) {
                continue;
            }

            self::establecer(
                $config['clave'],
                $config['valor'],
                $config['tipo'] ?? 'string',
                $config['opciones'] ?? []
            );
        }

        return true;
    }

    /**
     * Obtener configuraciones de logo específicamente
     */
    public static function obtenerConfiguracionLogo(): array
    {
        return [
            'logo_display' => self::obtener('app.logo_display', 'logo_text'),
            'logo_text' => self::obtener('app.logo_text', 'Laravel Starter Kit'),
            'logo_file' => self::obtener('app.logo_file', null),
        ];
    }

    /**
     * Establecer configuración de logo
     */
    public static function configurarLogo(array $datos): bool
    {
        $configuraciones = [
            [
                'clave' => 'app.logo_display',
                'valor' => $datos['logo_display'],
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'logo',
                    'publico' => true,
                    'descripcion' => 'Tipo de visualización del logo en el sidebar',
                    'validacion' => ['in:logo_text,logo_only']
                ]
            ],
            [
                'clave' => 'app.logo_text',
                'valor' => $datos['logo_text'],
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'logo',
                    'publico' => true,
                    'descripcion' => 'Texto que aparece junto al logo',
                    'validacion' => ['required', 'string', 'max:50']
                ]
            ]
        ];

        // Manejar archivo de logo
        if (isset($datos['logo_file'])) {
            $configuraciones[] = [
                'clave' => 'app.logo_file',
                'valor' => $datos['logo_file'],
                'tipo' => 'file',
                'opciones' => [
                    'categoria' => 'logo',
                    'publico' => true,
                    'descripcion' => 'Archivo de logo personalizado',
                    'validacion' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg', 'max:2048']
                ]
            ];
        }

        return self::configurarVarios($configuraciones);
    }

    /**
     * Manejar upload de archivo de logo
     */
    public static function manejarUploadLogo($archivo): ?string
    {
        if (!$archivo) {
            return null;
        }

        // Eliminar logo anterior si existe
        $logoAnterior = self::obtener('app.logo_file');
        if ($logoAnterior && Storage::disk('public')->exists($logoAnterior)) {
            Storage::disk('public')->delete($logoAnterior);
        }

        // Guardar nuevo logo
        $path = $archivo->store('logos', 'public');
        
        return $path;
    }

    /**
     * Eliminar logo anterior
     */
    public static function eliminarLogoAnterior(?string $logoPath): void
    {
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            Storage::disk('public')->delete($logoPath);
        }
    }

    /**
     * Obtener todas las configuraciones públicas para el frontend
     */
    public static function obtenerConfiguracionesPublicas(): array
    {
        return Configuracion::obtenerPublicas();
    }

    /**
     * Obtener configuraciones por categoría
     */
    public static function obtenerPorCategoria(string $categoria): array
    {
        return Configuracion::obtenerPorCategoria($categoria);
    }

    /**
     * Inicializar configuraciones por defecto del sistema
     */
    public static function inicializarConfiguracionesPorDefecto(): void
    {
        $configuracionesPorDefecto = [
            [
                'clave' => 'app.logo_display',
                'valor' => 'logo_text',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'logo',
                    'publico' => true,
                    'descripcion' => 'Tipo de visualización del logo en el sidebar',
                ]
            ],
            [
                'clave' => 'app.logo_text',
                'valor' => 'Laravel Starter Kit',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'logo',
                    'publico' => true,
                    'descripcion' => 'Texto que aparece junto al logo',
                ]
            ],
            [
                'clave' => 'app.nombre',
                'valor' => 'Sistema de Votaciones',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'general',
                    'publico' => true,
                    'descripcion' => 'Nombre del sistema',
                ]
            ],
            [
                'clave' => 'votaciones.tiempo_expiracion_otp',
                'valor' => '10',
                'tipo' => 'integer',
                'opciones' => [
                    'categoria' => 'votaciones',
                    'publico' => false,
                    'descripcion' => 'Tiempo de expiración de códigos OTP en minutos',
                ]
            ],
            [
                'clave' => 'email.driver',
                'valor' => 'smtp',
                'tipo' => 'string',
                'opciones' => [
                    'categoria' => 'email',
                    'publico' => false,
                    'descripcion' => 'Driver de email a utilizar',
                ]
            ]
        ];

        foreach ($configuracionesPorDefecto as $config) {
            // Solo crear si no existe
            if (!Configuracion::where('clave', $config['clave'])->exists()) {
                self::establecer(
                    $config['clave'],
                    $config['valor'],
                    $config['tipo'],
                    $config['opciones']
                );
            }
        }
    }

    /**
     * Limpiar cache de configuraciones
     */
    public static function limpiarCache(): void
    {
        Configuracion::limpiarCache();
    }
}