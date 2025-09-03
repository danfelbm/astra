<?php

namespace Modules\Asamblea\Services;

use Modules\Asamblea\Models\Asamblea;
use Modules\Asamblea\Repositories\AsambleaRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Servicio para manejar lógica de participantes públicos de asambleas
 */
class AsambleaPublicParticipantsService
{
    public function __construct(
        private AsambleaRepository $repository
    ) {}

    /**
     * Verificar si la consulta pública está disponible
     */
    public function verificarAccesoPublico(Asamblea $asamblea, ?string $mode = null): array
    {
        if (!$asamblea->public_participants_enabled) {
            return [
                'success' => false,
                'message' => 'La consulta pública de participantes no está disponible para esta asamblea.',
                'status' => 404
            ];
        }

        if (!$asamblea->activo) {
            return [
                'success' => false,
                'message' => 'La asamblea no está activa.',
                'status' => 404
            ];
        }

        if ($mode && $asamblea->public_participants_mode !== $mode) {
            return [
                'success' => false,
                'message' => 'Modo de consulta no disponible.',
                'status' => 404
            ];
        }

        return [
            'success' => true,
            'status' => 200
        ];
    }

    /**
     * Obtener datos públicos de la asamblea
     */
    public function getAsambleaPublicData(Asamblea $asamblea): array
    {
        $asamblea->load(['territorio', 'departamento', 'municipio', 'localidad']);

        return [
            'id' => $asamblea->id,
            'nombre' => $asamblea->nombre,
            'descripcion' => $asamblea->descripcion,
            'fecha_inicio' => $asamblea->fecha_inicio,
            'fecha_fin' => $asamblea->fecha_fin,
            'lugar' => $asamblea->lugar,
            'ubicacion_completa' => $asamblea->getUbicacionCompleta(),
            'public_participants_mode' => $asamblea->public_participants_mode,
        ];
    }

    /**
     * Obtener participantes públicos con caché (modo listado)
     */
    public function getPublicParticipants(Asamblea $asamblea, Request $request): array
    {
        // Verificar acceso
        $acceso = $this->verificarAccesoPublico($asamblea, 'list');
        if (!$acceso['success']) {
            return $acceso;
        }

        // Generar clave de caché única para esta consulta
        $cacheKey = 'asamblea_' . $asamblea->id . '_public_participants_' . md5(json_encode($request->all()));
        
        // Intentar obtener de caché (1 minuto)
        $participantes = Cache::remember($cacheKey, 60, function () use ($request, $asamblea) {
            return $this->repository->getPublicParticipants($asamblea, $request);
        });

        return [
            'success' => true,
            'status' => 200,
            'participantes' => $participantes,
            'filterFieldsConfig' => $this->getPublicFilterFieldsConfig()
        ];
    }

    /**
     * Buscar participante público con caché (modo búsqueda)
     */
    public function searchPublicParticipant(Asamblea $asamblea, string $search): array
    {
        // Verificar acceso
        $acceso = $this->verificarAccesoPublico($asamblea, 'search');
        if (!$acceso['success']) {
            return $acceso;
        }

        // Generar clave de caché para esta búsqueda
        $cacheKey = 'asamblea_' . $asamblea->id . '_search_' . md5($search);
        
        // Intentar obtener de caché (5 minutos)
        $result = Cache::remember($cacheKey, 300, function () use ($search, $asamblea) {
            return $this->repository->searchPublicParticipant($asamblea, $search);
        });

        return [
            'success' => true,
            'status' => 200,
            'result' => $result
        ];
    }

    /**
     * Obtener configuración de campos para filtros públicos
     */
    public function getPublicFilterFieldsConfig(): array
    {
        return [
            [
                'name' => 'users.name',
                'label' => 'Nombre',
                'type' => 'text',
            ],
            // Los campos geográficos se manejarán dinámicamente en el frontend
            // usando el composable useGeographicFilters con endpoints públicos
        ];
    }
}