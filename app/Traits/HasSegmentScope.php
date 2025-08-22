<?php

namespace App\Traits;

use App\Models\Segment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait HasSegmentScope
{
    /**
     * Aplicar automáticamente el scope del segmento del usuario actual
     * 
     * @param Builder $query Query base sobre la que aplicar el segmento
     * @param string $userTableAlias Alias de la tabla users en el join (ej: 'users' o 'u')
     * @return array|null Información del segmento aplicado o null si no hay segmento
     */
    protected function applySegmentScope(Builder $query, string $userTableAlias = 'users'): ?array
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }
        
        // Obtener segmentos asociados a los roles del usuario
        $segments = $this->getUserSegments();
        
        if ($segments->isEmpty()) {
            return null;
        }
        
        // Si hay múltiples segmentos, combinarlos con OR
        $query->where(function ($q) use ($segments, $userTableAlias) {
            foreach ($segments as $segment) {
                $q->orWhere(function ($subQuery) use ($segment, $userTableAlias) {
                    $this->applySegmentFilters($subQuery, $segment, $userTableAlias);
                });
            }
        });
        
        // Preparar información del segmento para el frontend
        $segmentInfo = [
            'applied' => true,
            'segments' => $segments->map(function ($segment) {
                return [
                    'id' => $segment->id,
                    'name' => $segment->name,
                    'description' => $segment->description,
                    'user_count' => $segment->getCount(),
                ];
            })->toArray(),
            'message' => $segments->count() > 1 
                ? 'Se están aplicando ' . $segments->count() . ' segmentos de usuario'
                : 'Se está aplicando el segmento: ' . $segments->first()->name,
        ];
        
        Log::info('Segmento aplicado a consulta', [
            'user_id' => $user->id,
            'segments' => $segments->pluck('name')->toArray(),
            'module' => class_basename($this),
        ]);
        
        return $segmentInfo;
    }
    
    /**
     * Obtener los segmentos asociados al usuario actual
     * 
     * @return \Illuminate\Support\Collection
     */
    protected function getUserSegments()
    {
        $user = Auth::user();
        
        if (!$user) {
            return collect();
        }
        
        // Obtener roles del usuario
        $roleIds = $user->roles()->pluck('roles.id');
        
        if ($roleIds->isEmpty()) {
            return collect();
        }
        
        // Obtener segmentos asociados a esos roles
        return Segment::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('roles.id', $roleIds);
        })
        ->where('model_type', 'App\\Models\\User') // Solo segmentos de usuarios
        ->where('is_dynamic', true) // Solo segmentos dinámicos
        ->get();
    }
    
    /**
     * Aplicar los filtros de un segmento específico a la query
     * 
     * @param Builder $query
     * @param Segment $segment
     * @param string $userTableAlias
     */
    protected function applySegmentFilters(Builder $query, Segment $segment, string $userTableAlias)
    {
        if (empty($segment->filters)) {
            return;
        }
        
        // Los filtros del segmento están en formato JSON
        $filters = $segment->filters;
        
        // Si tiene estructura de advanced_filters, usar esa
        if (isset($filters['advanced_filters'])) {
            $filters = $filters['advanced_filters'];
        }
        
        // Aplicar filtros usando la misma lógica que HasAdvancedFilters
        $this->applySegmentFilterGroup($query, $filters, $userTableAlias);
    }
    
    /**
     * Aplicar un grupo de filtros del segmento
     * Similar a applyFilterGroup pero con prefijo de tabla
     * 
     * @param Builder $query
     * @param array $group
     * @param string $userTableAlias
     */
    protected function applySegmentFilterGroup(Builder $query, array $group, string $userTableAlias): void
    {
        $operator = strtoupper($group['operator'] ?? 'AND');
        $conditions = $group['conditions'] ?? [];
        $subgroups = $group['groups'] ?? [];
        
        if ($operator === 'OR') {
            $query->where(function ($q) use ($conditions, $subgroups, $userTableAlias) {
                foreach ($conditions as $condition) {
                    $q->orWhere(function ($subQ) use ($condition, $userTableAlias) {
                        $this->applySegmentCondition($subQ, $condition, $userTableAlias);
                    });
                }
                
                foreach ($subgroups as $subgroup) {
                    $q->orWhere(function ($subQ) use ($subgroup, $userTableAlias) {
                        $this->applySegmentFilterGroup($subQ, $subgroup, $userTableAlias);
                    });
                }
            });
        } else {
            // AND es el operador por defecto
            foreach ($conditions as $condition) {
                $this->applySegmentCondition($query, $condition, $userTableAlias);
            }
            
            foreach ($subgroups as $subgroup) {
                $query->where(function ($q) use ($subgroup, $userTableAlias) {
                    $this->applySegmentFilterGroup($q, $subgroup, $userTableAlias);
                });
            }
        }
    }
    
    /**
     * Aplicar una condición individual del segmento
     * 
     * @param Builder $query
     * @param array $condition
     * @param string $userTableAlias
     */
    protected function applySegmentCondition(Builder $query, array $condition, string $userTableAlias): void
    {
        $field = $condition['field'] ?? $condition['name'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;
        
        if (!$field) {
            return;
        }
        
        // Añadir prefijo de tabla si el campo no lo tiene
        if (!str_contains($field, '.')) {
            $field = $userTableAlias . '.' . $field;
        }
        
        // Aplicar según el operador
        switch ($operator) {
            case 'equals':
                $query->where($field, '=', $value);
                break;
                
            case 'not_equals':
                $query->where($field, '!=', $value);
                break;
                
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
                
            case 'is_empty':
                $query->where(function ($q) use ($field) {
                    $q->whereNull($field)
                      ->orWhere($field, '=', '');
                });
                break;
                
            case 'is_not_empty':
                $query->where(function ($q) use ($field) {
                    $q->whereNotNull($field)
                      ->where($field, '!=', '');
                });
                break;
                
            case 'greater_than':
                $query->where($field, '>', $value);
                break;
                
            case 'less_than':
                $query->where($field, '<', $value);
                break;
                
            case 'greater_or_equal':
                $query->where($field, '>=', $value);
                break;
                
            case 'less_or_equal':
                $query->where($field, '<=', $value);
                break;
                
            case 'between':
                if (is_array($value) && count($value) >= 2) {
                    $query->whereBetween($field, [$value[0], $value[1]]);
                }
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
        }
    }
    
    /**
     * Combinar filtros del segmento con filtros avanzados del request
     * Los filtros del request se aplican DESPUÉS del segmento
     * 
     * @param Request $request
     * @return string|null JSON string de filtros combinados
     */
    protected function mergeSegmentWithAdvancedFilters(Request $request): ?string
    {
        $segments = $this->getUserSegments();
        
        if ($segments->isEmpty()) {
            // No hay segmentos, devolver filtros del request tal cual
            return $request->get('advanced_filters');
        }
        
        // Si hay filtros avanzados en el request, combinarlos con AND
        if ($request->filled('advanced_filters')) {
            $requestFilters = json_decode($request->advanced_filters, true);
            
            // Crear un grupo principal con AND que combine segmento y filtros del request
            $combinedFilters = [
                'operator' => 'AND',
                'groups' => [],
                'conditions' => []
            ];
            
            // Añadir grupo del segmento
            if ($segments->count() > 1) {
                // Múltiples segmentos, combinar con OR
                $segmentGroup = [
                    'operator' => 'OR',
                    'groups' => [],
                    'conditions' => []
                ];
                
                foreach ($segments as $segment) {
                    $segmentFilters = $segment->filters['advanced_filters'] ?? $segment->filters;
                    $segmentGroup['groups'][] = $segmentFilters;
                }
                
                $combinedFilters['groups'][] = $segmentGroup;
            } else {
                // Un solo segmento
                $segmentFilters = $segments->first()->filters['advanced_filters'] ?? $segments->first()->filters;
                $combinedFilters['groups'][] = $segmentFilters;
            }
            
            // Añadir filtros del request
            $combinedFilters['groups'][] = $requestFilters;
            
            return json_encode($combinedFilters);
        }
        
        // No hay filtros en el request, solo aplicar segmento
        return null;
    }
    
    /**
     * Verificar si el usuario actual tiene segmentos aplicables para un módulo
     * 
     * @param string $permission Permiso a verificar (ej: 'candidaturas.view')
     * @return bool
     */
    protected function hasApplicableSegment(string $permission): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Verificar si el usuario tiene el permiso
        if (!$user->hasPermission($permission)) {
            return false;
        }
        
        // Verificar si tiene segmentos
        return !$this->getUserSegments()->isEmpty();
    }
}