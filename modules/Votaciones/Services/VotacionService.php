<?php

namespace Modules\Votaciones\Services;

use Modules\Votaciones\Models\Votacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VotacionService
{
    /**
     * Crear una nueva votación
     */
    public function create(array $data): Votacion
    {
        // Convertir fechas de zona horaria seleccionada a UTC para almacenamiento
        $this->prepareDatesForStorage($data);

        return Votacion::create([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'categoria_id' => $data['categoria_id'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'estado' => $data['estado'] ?? 'borrador',
            'resultados_publicos' => $data['resultados_publicos'] ?? false,
            'allow_tokens_download' => $data['allow_tokens_download'] ?? false,
            'fecha_publicacion_resultados' => $data['fecha_publicacion_resultados'] ?? null,
            'limite_censo' => $data['limite_censo'] ?? null,
            'mensaje_limite_censo' => $data['mensaje_limite_censo'] ?? null,
            'formulario_config' => $data['formulario_config'],
            'timezone' => $data['timezone'],
            'territorios_ids' => $data['territorios_ids'] ?? null,
            'departamentos_ids' => $data['departamentos_ids'] ?? null,
            'municipios_ids' => $data['municipios_ids'] ?? null,
            'localidades_ids' => $data['localidades_ids'] ?? null,
        ]);
    }

    /**
     * Actualizar una votación existente
     */
    public function update(Votacion $votacion, array $data): Votacion
    {
        // Convertir fechas de zona horaria seleccionada a UTC para almacenamiento
        $this->prepareDatesForStorage($data);

        $votacion->update([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'categoria_id' => $data['categoria_id'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'estado' => $data['estado'],
            'resultados_publicos' => $data['resultados_publicos'] ?? false,
            'allow_tokens_download' => $data['allow_tokens_download'] ?? false,
            'fecha_publicacion_resultados' => $data['fecha_publicacion_resultados'] ?? null,
            'limite_censo' => $data['limite_censo'] ?? null,
            'mensaje_limite_censo' => $data['mensaje_limite_censo'] ?? null,
            'formulario_config' => $data['formulario_config'],
            'timezone' => $data['timezone'],
            'territorios_ids' => $data['territorios_ids'] ?? null,
            'departamentos_ids' => $data['departamentos_ids'] ?? null,
            'municipios_ids' => $data['municipios_ids'] ?? null,
            'localidades_ids' => $data['localidades_ids'] ?? null,
        ]);

        return $votacion;
    }

    /**
     * Eliminar una votación
     */
    public function delete(Votacion $votacion): array
    {
        // No permitir eliminar votaciones activas o finalizadas
        if (in_array($votacion->estado, ['activa', 'finalizada'])) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar una votación que está activa o finalizada.'
            ];
        }

        $titulo = $votacion->titulo;
        $votacion->delete();

        return [
            'success' => true,
            'message' => "Votación '{$titulo}' eliminada exitosamente."
        ];
    }

    /**
     * Cambiar el estado de una votación
     */
    public function toggleStatus(Votacion $votacion, string $nuevoEstado): array
    {
        // Validar estado válido
        if (!in_array($nuevoEstado, ['borrador', 'activa', 'finalizada'])) {
            return [
                'success' => false,
                'message' => 'Estado no válido.'
            ];
        }

        // Validaciones de negocio
        if ($votacion->estado === 'finalizada') {
            return [
                'success' => false,
                'message' => 'No se puede cambiar el estado de una votación finalizada.'
            ];
        }

        // Si se está activando, validar que tenga al menos un votante
        if ($nuevoEstado === 'activa' && $votacion->votantes()->count() === 0) {
            return [
                'success' => false,
                'message' => 'No se puede activar una votación sin votantes asignados.'
            ];
        }

        // Si se está activando, validar que las fechas sean coherentes
        if ($nuevoEstado === 'activa') {
            $now = $votacion->ahora();
            $fechaFin = $votacion->enZonaHoraria($votacion->fecha_fin);

            if ($fechaFin <= $now) {
                return [
                    'success' => false,
                    'message' => 'No se puede activar una votación cuya fecha de fin ya ha pasado.'
                ];
            }
        }

        $estadoAnterior = $votacion->estado;
        $votacion->update(['estado' => $nuevoEstado]);

        $mensaje = $this->getMensajeCambioEstado($estadoAnterior, $nuevoEstado, $votacion->titulo);

        return [
            'success' => true,
            'message' => $mensaje
        ];
    }

    /**
     * Preparar fechas para almacenamiento (convertir a UTC)
     */
    private function prepareDatesForStorage(array &$data): void
    {
        if (isset($data['fecha_inicio']) && isset($data['timezone'])) {
            $data['fecha_inicio'] = Carbon::parse($data['fecha_inicio'], $data['timezone'])->utc();
        }

        if (isset($data['fecha_fin']) && isset($data['timezone'])) {
            $data['fecha_fin'] = Carbon::parse($data['fecha_fin'], $data['timezone'])->utc();
        }

        if (isset($data['fecha_publicacion_resultados']) && $data['fecha_publicacion_resultados'] && isset($data['timezone'])) {
            $data['fecha_publicacion_resultados'] = Carbon::parse($data['fecha_publicacion_resultados'], $data['timezone'])->utc();
        } else {
            $data['fecha_publicacion_resultados'] = null;
        }

        if (isset($data['limite_censo']) && $data['limite_censo'] && isset($data['timezone'])) {
            $data['limite_censo'] = Carbon::parse($data['limite_censo'], $data['timezone'])->utc();
        } else {
            $data['limite_censo'] = null;
        }
    }

    /**
     * Generar mensaje apropiado para el cambio de estado
     */
    private function getMensajeCambioEstado(string $estadoAnterior, string $nuevoEstado, string $titulo): string
    {
        $acciones = [
            'borrador' => [
                'activa' => "Votación '{$titulo}' activada exitosamente.",
                'finalizada' => "Votación '{$titulo}' finalizada exitosamente."
            ],
            'activa' => [
                'borrador' => "Votación '{$titulo}' desactivada y vuelve al estado borrador.",
                'finalizada' => "Votación '{$titulo}' finalizada exitosamente."
            ]
        ];

        return $acciones[$estadoAnterior][$nuevoEstado] ?? "Estado de votación '{$titulo}' cambiado exitosamente.";
    }
}
