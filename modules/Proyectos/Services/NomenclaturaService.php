<?php

namespace Modules\Proyectos\Services;

use Illuminate\Support\Str;
use Modules\Proyectos\Models\Proyecto;
use Modules\Proyectos\Models\Hito;
use Modules\Proyectos\Models\Entregable;

/**
 * Servicio para gestionar la nomenclatura de archivos de evidencias.
 * Procesa patrones con tokens y genera nombres/rutas de archivos.
 */
class NomenclaturaService
{
    /**
     * Tokens disponibles para la nomenclatura.
     */
    protected array $tokensDisponibles = [
        '{proyecto}' => 'Nombre del proyecto (slug)',
        '{proyecto_id}' => 'ID del proyecto',
        '{hito}' => 'Nombre del hito (slug)',
        '{hito_id}' => 'ID del hito',
        '{entregable}' => 'Nombre del entregable (slug)',
        '{entregable_id}' => 'ID del entregable',
        '{fecha}' => 'Fecha actual (Y-m-d)',
        '{fecha:Ymd}' => 'Fecha formato compacto',
        '{fecha:d-m-Y}' => 'Fecha formato europeo',
        '{original}' => 'Nombre original del archivo (slug)',
    ];

    /**
     * Genera el nombre del archivo basado en el patrón configurado.
     * Siempre agrega _{uid} al final para garantizar unicidad.
     *
     * @param string|null $patron Patrón de nomenclatura (null = usar default)
     * @param array $contexto Datos del contexto (proyecto, hito, entregable, original, extension)
     * @return string Nombre de archivo generado (sin extensión, se agrega después)
     */
    public function generarNombre(?string $patron, array $contexto): string
    {
        // Patrón por defecto si no hay configuración
        if (empty($patron)) {
            $patron = '{original}';
        }

        // Procesar tokens de fecha primero (tienen formato especial)
        $nombre = $this->procesarTokensFecha($patron);

        // Procesar tokens estándar
        $nombre = $this->procesarTokensEstandar($nombre, $contexto);

        // Limpiar el nombre (slug) y agregar UID único
        $nombre = Str::slug($nombre);
        $uid = substr(uniqid(), -6); // Últimos 6 caracteres del uniqid

        return $nombre . '_' . $uid;
    }

    /**
     * Genera el nombre completo del archivo incluyendo la extensión.
     */
    public function generarNombreCompleto(?string $patron, array $contexto): string
    {
        $nombre = $this->generarNombre($patron, $contexto);
        $extension = $contexto['extension'] ?? 'bin';

        return $nombre . '.' . strtolower($extension);
    }

    /**
     * Genera la ruta de almacenamiento para evidencias.
     * Estructura: evidencias/{proyecto_id}/{hito_id}/{entregable_id}/
     */
    public function generarRuta(int $proyectoId, ?int $hitoId = null, ?int $entregableId = null): string
    {
        $ruta = "evidencias/{$proyectoId}";

        if ($hitoId) {
            $ruta .= "/{$hitoId}";
        }

        if ($entregableId) {
            $ruta .= "/{$entregableId}";
        }

        return $ruta;
    }

    /**
     * Genera la ruta completa (carpeta + nombre de archivo).
     */
    public function generarRutaCompleta(?string $patron, array $contexto): string
    {
        $proyectoId = $contexto['proyecto_id'] ?? 0;
        $hitoId = $contexto['hito_id'] ?? null;
        $entregableId = $contexto['entregable_id'] ?? null;

        $ruta = $this->generarRuta($proyectoId, $hitoId, $entregableId);
        $nombre = $this->generarNombreCompleto($patron, $contexto);

        return $ruta . '/' . $nombre;
    }

    /**
     * Procesa los tokens de fecha con formatos personalizados.
     * Soporta: {fecha}, {fecha:Ymd}, {fecha:d-m-Y}, etc.
     */
    protected function procesarTokensFecha(string $patron): string
    {
        // Primero procesar tokens con formato específico: {fecha:formato}
        $patron = preg_replace_callback(
            '/\{fecha:([^}]+)\}/',
            function ($matches) {
                return date($matches[1]);
            },
            $patron
        );

        // Luego procesar el token simple {fecha} -> Y-m-d
        $patron = str_replace('{fecha}', date('Y-m-d'), $patron);

        return $patron;
    }

    /**
     * Procesa los tokens estándar (proyecto, hito, entregable, original).
     */
    protected function procesarTokensEstandar(string $patron, array $contexto): string
    {
        $reemplazos = [];

        // Tokens de proyecto
        if (isset($contexto['proyecto'])) {
            $proyecto = $contexto['proyecto'];
            $reemplazos['{proyecto}'] = Str::slug($proyecto->nombre ?? '');
            $reemplazos['{proyecto_id}'] = $proyecto->id ?? '';
        } elseif (isset($contexto['proyecto_id'])) {
            $reemplazos['{proyecto_id}'] = $contexto['proyecto_id'];
            // Si solo tenemos el ID, intentar cargar el proyecto
            if ($proyecto = Proyecto::find($contexto['proyecto_id'])) {
                $reemplazos['{proyecto}'] = Str::slug($proyecto->nombre);
            } else {
                $reemplazos['{proyecto}'] = 'proyecto-' . $contexto['proyecto_id'];
            }
        }

        // Tokens de hito
        if (isset($contexto['hito'])) {
            $hito = $contexto['hito'];
            $reemplazos['{hito}'] = Str::slug($hito->nombre ?? '');
            $reemplazos['{hito_id}'] = $hito->id ?? '';
        } elseif (isset($contexto['hito_id'])) {
            $reemplazos['{hito_id}'] = $contexto['hito_id'];
            if ($hito = Hito::find($contexto['hito_id'])) {
                $reemplazos['{hito}'] = Str::slug($hito->nombre);
            } else {
                $reemplazos['{hito}'] = 'hito-' . $contexto['hito_id'];
            }
        }

        // Tokens de entregable
        if (isset($contexto['entregable'])) {
            $entregable = $contexto['entregable'];
            $reemplazos['{entregable}'] = Str::slug($entregable->nombre ?? '');
            $reemplazos['{entregable_id}'] = $entregable->id ?? '';
        } elseif (isset($contexto['entregable_id'])) {
            $reemplazos['{entregable_id}'] = $contexto['entregable_id'];
            if ($entregable = Entregable::find($contexto['entregable_id'])) {
                $reemplazos['{entregable}'] = Str::slug($entregable->nombre);
            } else {
                $reemplazos['{entregable}'] = 'entregable-' . $contexto['entregable_id'];
            }
        }

        // Token de nombre original
        if (isset($contexto['original'])) {
            $reemplazos['{original}'] = Str::slug(
                pathinfo($contexto['original'], PATHINFO_FILENAME)
            );
        }

        return str_replace(array_keys($reemplazos), array_values($reemplazos), $patron);
    }

    /**
     * Obtiene la lista de tokens disponibles con sus descripciones.
     */
    public function getTokensDisponibles(): array
    {
        return $this->tokensDisponibles;
    }

    /**
     * Obtiene los tokens como array para el frontend.
     */
    public function getTokensParaFrontend(): array
    {
        $tokens = [];
        foreach ($this->tokensDisponibles as $token => $descripcion) {
            $tokens[] = [
                'token' => $token,
                'descripcion' => $descripcion,
            ];
        }
        return $tokens;
    }

    /**
     * Valida que un patrón de nomenclatura sea válido.
     * Un patrón es válido si:
     * - Está vacío (se usará el default)
     * - O contiene al menos un token válido
     * - Y no tiene tokens inválidos/malformados
     */
    public function validarPatron(string $patron): bool
    {
        // Vacío es válido (usa default)
        if (empty(trim($patron))) {
            return true;
        }

        // Buscar tokens en el patrón
        preg_match_all('/\{([^}]+)\}/', $patron, $matches);

        if (empty($matches[0])) {
            // No tiene tokens, pero tiene contenido - es válido (texto literal)
            return true;
        }

        // Verificar que todos los tokens encontrados sean válidos
        $tokensValidos = array_keys($this->tokensDisponibles);
        // Agregar patrón de fecha con formato variable
        $patronFecha = '/^\{fecha:[^}]+\}$/';

        foreach ($matches[0] as $token) {
            // Verificar si es un token de fecha con formato
            if (preg_match('/^\{fecha:.+\}$/', $token)) {
                continue; // Es válido
            }

            // Verificar si es un token estándar
            if (!in_array($token, $tokensValidos)) {
                return false; // Token no reconocido
            }
        }

        return true;
    }

    /**
     * Genera un preview del nombre de archivo para mostrar en el frontend.
     */
    public function generarPreview(string $patron): string
    {
        $contexto = [
            'proyecto_id' => 42,
            'hito_id' => 15,
            'entregable_id' => 78,
            'original' => 'mi-documento.pdf',
            'extension' => 'pdf',
        ];

        // Crear objetos mock para el preview
        $proyecto = new \stdClass();
        $proyecto->id = 42;
        $proyecto->nombre = 'Mi Proyecto';
        $contexto['proyecto'] = $proyecto;

        $hito = new \stdClass();
        $hito->id = 15;
        $hito->nombre = 'Fase 1';
        $contexto['hito'] = $hito;

        $entregable = new \stdClass();
        $entregable->id = 78;
        $entregable->nombre = 'Documento Final';
        $contexto['entregable'] = $entregable;

        return $this->generarNombreCompleto($patron, $contexto);
    }
}
