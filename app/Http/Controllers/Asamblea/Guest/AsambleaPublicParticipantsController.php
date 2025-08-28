<?php

namespace App\Http\Controllers\Asamblea\Guest;

use App\Http\Controllers\Core\GuestController;


use App\Models\Asamblea\Asamblea;
use App\Models\Core\User;
use App\Traits\HasAdvancedFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AsambleaPublicParticipantsController extends GuestController
{
    use HasAdvancedFilters;

    /**
     * Mostrar vista pública de participantes
     */
    public function show(Request $request, Asamblea $asamblea): Response
    {
        // Verificar que la consulta pública esté habilitada
        if (!$asamblea->public_participants_enabled) {
            abort(404, 'La consulta pública de participantes no está disponible para esta asamblea.');
        }

        // Verificar que la asamblea esté activa
        if (!$asamblea->activo) {
            abort(404, 'La asamblea no está activa.');
        }

        // Cargar datos básicos de la asamblea
        $asamblea->load(['territorio', 'departamento', 'municipio', 'localidad']);

        // Preparar datos básicos para la vista
        $asambleaData = [
            'id' => $asamblea->id,
            'nombre' => $asamblea->nombre,
            'descripcion' => $asamblea->descripcion,
            'fecha_inicio' => $asamblea->fecha_inicio,
            'fecha_fin' => $asamblea->fecha_fin,
            'lugar' => $asamblea->lugar,
            'ubicacion_completa' => $asamblea->getUbicacionCompleta(),
            'public_participants_mode' => $asamblea->public_participants_mode,
        ];

        // Renderizar vista según el modo configurado
        if ($asamblea->public_participants_mode === 'list') {
            return Inertia::render('Guest/Asambleas/ParticipantsList', [
                'asamblea' => $asambleaData,
                'filterFieldsConfig' => $this->getPublicFilterFieldsConfig(),
            ]);
        } else {
            return Inertia::render('Guest/Asambleas/ParticipantsSearch', [
                'asamblea' => $asambleaData,
            ]);
        }
    }

    /**
     * API para obtener participantes en modo listado (con caché)
     */
    public function getParticipants(Request $request, Asamblea $asamblea)
    {
        // Verificar que la consulta pública esté habilitada
        if (!$asamblea->public_participants_enabled || $asamblea->public_participants_mode !== 'list') {
            abort(404);
        }

        // Verificar que la asamblea esté activa
        if (!$asamblea->activo) {
            abort(404);
        }

        // Generar clave de caché única para esta consulta
        $cacheKey = 'asamblea_' . $asamblea->id . '_public_participants_' . md5(json_encode($request->all()));
        
        // Intentar obtener de caché (1 minuto)
        $result = Cache::remember($cacheKey, 60, function () use ($request, $asamblea) {
            // Construir query con joins para incluir datos geográficos
            $query = User::query()
                ->join('asamblea_usuario', 'users.id', '=', 'asamblea_usuario.usuario_id')
                ->leftJoin('territorios', 'users.territorio_id', '=', 'territorios.id')
                ->leftJoin('departamentos', 'users.departamento_id', '=', 'departamentos.id')
                ->leftJoin('municipios', 'users.municipio_id', '=', 'municipios.id')
                ->leftJoin('localidades', 'users.localidad_id', '=', 'localidades.id')
                ->where('asamblea_usuario.asamblea_id', $asamblea->id);
            
            // Seleccionar SOLO campos públicos permitidos
            $query->select(
                'users.id',
                'users.name',
                'territorios.nombre as territorio_nombre',
                'departamentos.nombre as departamento_nombre',
                'municipios.nombre as municipio_nombre',
                'localidades.nombre as localidad_nombre'
            );

            // Definir campos permitidos para filtrar (limitados)
            $allowedFields = [
                'users.name',
                'users.territorio_id',
                'users.departamento_id',
                'users.municipio_id',
                'users.localidad_id',
            ];
            
            // Campos para búsqueda rápida (nombre y cédula)
            $quickSearchFields = ['users.name', 'users.documento_identidad'];

            // Aplicar filtros avanzados
            $this->applyAdvancedFilters($query, $request, $allowedFields, $quickSearchFields);

            // Ordenamiento
            $query->orderBy('users.name');

            // Paginar con límite de 50 por página
            $participantes = $query->paginate(50)->withQueryString();

            // Transformar datos para asegurar que solo se envían campos públicos
            $participantes->getCollection()->transform(function ($participante) {
                return [
                    'id' => $participante->id,
                    'name' => $participante->name,
                    'territorio_nombre' => $participante->territorio_nombre,
                    'departamento_nombre' => $participante->departamento_nombre,
                    'municipio_nombre' => $participante->municipio_nombre,
                    'localidad_nombre' => $participante->localidad_nombre,
                ];
            });

            return $participantes;
        });

        return response()->json([
            'participantes' => $result,
            'filterFieldsConfig' => $this->getPublicFilterFieldsConfig(),
        ]);
    }

    /**
     * API para búsqueda de participantes (modo búsqueda)
     */
    public function search(Request $request, Asamblea $asamblea)
    {
        // Verificar que la consulta pública esté habilitada
        if (!$asamblea->public_participants_enabled || $asamblea->public_participants_mode !== 'search') {
            abort(404);
        }

        // Verificar que la asamblea esté activa
        if (!$asamblea->activo) {
            abort(404);
        }

        // Validar entrada
        $request->validate([
            'search' => 'required|string|min:3|max:100',
        ], [
            'search.required' => 'Por favor ingrese un término de búsqueda.',
            'search.min' => 'El término de búsqueda debe tener al menos 3 caracteres.',
            'search.max' => 'El término de búsqueda no puede exceder 100 caracteres.',
        ]);

        $search = $request->input('search');

        // Generar clave de caché para esta búsqueda
        $cacheKey = 'asamblea_' . $asamblea->id . '_search_' . md5($search);
        
        // Intentar obtener de caché (5 minutos)
        $result = Cache::remember($cacheKey, 300, function () use ($search, $asamblea) {
            // Buscar si existe un participante que coincida
            $participante = User::query()
                ->join('asamblea_usuario', 'users.id', '=', 'asamblea_usuario.usuario_id')
                ->where('asamblea_usuario.asamblea_id', $asamblea->id)
                ->where(function($q) use ($search) {
                    $q->where('users.name', 'like', '%' . $search . '%')
                      ->orWhere('users.email', $search)
                      ->orWhere('users.documento_identidad', $search);
                })
                ->select('users.name')
                ->first();

            if ($participante) {
                return [
                    'found' => true,
                    'message' => $participante->name . ' es participante de esta asamblea.',
                ];
            } else {
                return [
                    'found' => false,
                    'message' => 'No se encontró ningún participante con los datos proporcionados.',
                ];
            }
        });

        return response()->json($result);
    }

    /**
     * Configuración de campos para filtros públicos (modo listado)
     */
    protected function getPublicFilterFieldsConfig(): array
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