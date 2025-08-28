<?php

namespace App\Http\Controllers\Elecciones\Guest;

use App\Http\Controllers\Core\GuestController;

use App\Models\Elecciones\Postulacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PostulacionPublicApiController extends GuestController
{
    /**
     * Obtener postulaciones aceptadas con filtros
     */
    public function index(Request $request)
    {
        // Crear clave de cache basada en los parámetros de la request
        $cacheKey = 'postulaciones_publicas_' . md5(json_encode($request->all()));
        
        // Intentar obtener del cache (5 minutos)
        $result = Cache::remember($cacheKey, 300, function () use ($request) {
            // Construir query con todos los JOINs necesarios desde el principio
            $query = Postulacion::query()
                ->join('users', 'postulaciones.user_id', '=', 'users.id')
                ->join('convocatorias', 'postulaciones.convocatoria_id', '=', 'convocatorias.id')
                ->leftJoin('cargos', 'convocatorias.cargo_id', '=', 'cargos.id')
                ->leftJoin('periodos_electorales', 'convocatorias.periodo_electoral_id', '=', 'periodos_electorales.id')
                // JOINs para ubicación del usuario
                ->leftJoin('territorios as user_territorios', 'users.territorio_id', '=', 'user_territorios.id')
                ->leftJoin('departamentos as user_departamentos', 'users.departamento_id', '=', 'user_departamentos.id')
                ->leftJoin('municipios as user_municipios', 'users.municipio_id', '=', 'user_municipios.id')
                ->leftJoin('localidades as user_localidades', 'users.localidad_id', '=', 'user_localidades.id');

            // Seleccionar campos específicos para evitar ambigüedades y datos sensibles
            $query->select(
                'postulaciones.id',
                'postulaciones.fecha_postulacion',
                'postulaciones.revisado_at',
                'postulaciones.convocatoria_id',
                'users.id as user_id',
                'users.name as user_name',
                'users.documento_identidad',
                'convocatorias.id as convocatoria_id',
                'convocatorias.nombre as convocatoria_nombre',
                'convocatorias.cargo_id',
                'convocatorias.periodo_electoral_id',
                'cargos.nombre as cargo_nombre',
                'periodos_electorales.nombre as periodo_nombre',
                // Ubicación del usuario
                'user_territorios.nombre as user_territorio_nombre',
                'user_departamentos.nombre as user_departamento_nombre',
                'user_municipios.nombre as user_municipio_nombre',
                'user_localidades.nombre as user_localidad_nombre',
                'users.territorio_id',
                'users.departamento_id',
                'users.municipio_id',
                'users.localidad_id'
            );

            // Aplicar filtro de solo aceptadas
            $query->where('postulaciones.estado', 'aceptada');

            // Aplicar búsqueda rápida si existe
            if ($request->filled('search')) {
                $searchTerm = $request->get('search');
                $query->where(function ($q) use ($searchTerm) {
                    // Dividir el término de búsqueda en palabras
                    $searchWords = preg_split('/\s+/', trim($searchTerm));
                    
                    // Buscar en nombre
                    $q->where(function ($subQuery) use ($searchWords) {
                        foreach ($searchWords as $word) {
                            if (!empty($word)) {
                                $subQuery->where('users.name', 'like', "%{$word}%");
                            }
                        }
                    });
                    
                    // O buscar en documento de identidad
                    $q->orWhere('users.documento_identidad', 'like', "%{$searchTerm}%");
                    
                    // O buscar en nombre de convocatoria
                    $q->orWhere('convocatorias.nombre', 'like', "%{$searchTerm}%");
                });
            }

            // Aplicar filtros avanzados si existen
            if ($request->filled('advanced_filters')) {
                $filters = json_decode($request->get('advanced_filters'), true);
                if ($filters && isset($filters['conditions'])) {
                    $this->applyAdvancedFilters($query, $filters);
                }
            }

            // Ordenamiento
            $query->orderBy('postulaciones.revisado_at', 'desc');

            // Paginar resultados
            $postulaciones = $query->paginate(50)->withQueryString();

            // Transformar datos para el frontend (solo información pública)
            $postulaciones->getCollection()->transform(function ($postulacion) {
                return [
                    'id' => $postulacion->id,
                    'postulante' => [
                        'nombre' => $postulacion->user_name ?: 'Información no disponible',
                    ],
                    'convocatoria' => [
                        'id' => $postulacion->convocatoria_id,
                        'nombre' => $postulacion->convocatoria_nombre,
                        'cargo' => $postulacion->cargo_nombre,
                        'periodo' => $postulacion->periodo_nombre,
                    ],
                    'ubicacion' => [
                        // Usar ubicación del usuario
                        'territorio' => $postulacion->user_territorio_nombre,
                        'departamento' => $postulacion->user_departamento_nombre,
                        'municipio' => $postulacion->user_municipio_nombre,
                        'localidad' => $postulacion->user_localidad_nombre,
                    ],
                    'fecha_aceptacion' => $postulacion->revisado_at ? date('d/m/Y', strtotime($postulacion->revisado_at)) : null,
                    'fecha_postulacion' => $postulacion->fecha_postulacion ? date('d/m/Y', strtotime($postulacion->fecha_postulacion)) : null,
                ];
            });

            return $postulaciones;
        });

        return response()->json([
            'postulaciones' => $result,
            'filterFieldsConfig' => $this->getFilterFieldsConfig(),
        ]);
    }

    /**
     * Aplicar filtros avanzados
     */
    protected function applyAdvancedFilters($query, $filters, $logic = 'and')
    {
        $logic = strtolower($filters['logic'] ?? 'and');
        
        // Procesar condiciones
        if (isset($filters['conditions']) && is_array($filters['conditions'])) {
            $query->where(function($q) use ($filters, $logic) {
                foreach ($filters['conditions'] as $condition) {
                    if (!isset($condition['field']) || !isset($condition['operator']) || !isset($condition['value'])) {
                        continue;
                    }

                    $field = $condition['field'];
                    $operator = $condition['operator'];
                    $value = $condition['value'];

                    // Mapear operadores
                    $operatorMap = [
                        'equals' => '=',
                        'not_equals' => '!=',
                        'contains' => 'like',
                        'not_contains' => 'not like',
                        'starts_with' => 'like',
                        'ends_with' => 'like',
                        'greater_than' => '>',
                        'less_than' => '<',
                        'greater_or_equal' => '>=',
                        'less_or_equal' => '<=',
                        'between' => 'between',
                        'not_between' => 'not between',
                        'is_null' => 'is null',
                        'is_not_null' => 'is not null',
                        'in' => 'in',
                        'not_in' => 'not in',
                    ];

                    $sqlOperator = $operatorMap[$operator] ?? '=';

                    // Aplicar el filtro según el operador
                    if ($logic === 'or') {
                        $q->orWhere(function($subQuery) use ($field, $sqlOperator, $value, $operator) {
                            $this->applyCondition($subQuery, $field, $sqlOperator, $value, $operator);
                        });
                    } else {
                        $this->applyCondition($q, $field, $sqlOperator, $value, $operator);
                    }
                }
            });
        }

        // Procesar grupos anidados
        if (isset($filters['groups']) && is_array($filters['groups'])) {
            foreach ($filters['groups'] as $group) {
                if ($logic === 'or') {
                    $query->orWhere(function($q) use ($group) {
                        $this->applyAdvancedFilters($q, $group, $group['logic'] ?? 'and');
                    });
                } else {
                    $query->where(function($q) use ($group) {
                        $this->applyAdvancedFilters($q, $group, $group['logic'] ?? 'and');
                    });
                }
            }
        }
    }

    /**
     * Aplicar una condición individual
     */
    protected function applyCondition($query, $field, $sqlOperator, $value, $operator)
    {
        // Manejo especial para operadores
        switch ($operator) {
            case 'contains':
                $query->where($field, 'like', "%{$value}%");
                break;
            case 'not_contains':
                $query->where($field, 'not like', "%{$value}%");
                break;
            case 'starts_with':
                $query->where($field, 'like', "{$value}%");
                break;
            case 'ends_with':
                $query->where($field, 'like', "%{$value}");
                break;
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($field, $value);
                }
                break;
            case 'not_between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereNotBetween($field, $value);
                }
                break;
            case 'is_null':
                $query->whereNull($field);
                break;
            case 'is_not_null':
                $query->whereNotNull($field);
                break;
            case 'in':
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                }
                break;
            case 'not_in':
                if (is_array($value)) {
                    $query->whereNotIn($field, $value);
                }
                break;
            default:
                $query->where($field, $sqlOperator, $value);
                break;
        }
    }

    /**
     * Obtener configuración de campos para filtros avanzados
     */
    protected function getFilterFieldsConfig(): array
    {
        // Usar la misma configuración del controlador web
        $controller = new \App\Http\Controllers\Elecciones\Guest\PostulacionPublicController();
        return $controller->getFilterFieldsConfig();
    }
}