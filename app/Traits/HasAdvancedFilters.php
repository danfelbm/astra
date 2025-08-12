<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasAdvancedFilters
{
    /**
     * Aplicar filtros avanzados a una consulta
     * 
     * @param Builder $query
     * @param Request $request
     * @param array $allowedFields Campos permitidos para filtrar
     * @param array $quickSearchFields Campos para búsqueda rápida
     * @return Builder
     */
    public function applyAdvancedFilters(
        Builder $query, 
        Request $request, 
        array $allowedFields = [], 
        array $quickSearchFields = []
    ): Builder {
        // Búsqueda rápida
        if ($request->filled('search') && !empty($quickSearchFields)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm, $quickSearchFields) {
                foreach ($quickSearchFields as $field) {
                    $q->orWhere($field, 'like', "%{$searchTerm}%");
                }
            });
        }

        // Filtros avanzados
        if ($request->filled('advanced_filters')) {
            $filters = json_decode($request->advanced_filters, true);
            
            if ($filters) {
                $this->applyFilterGroup($query, $filters, $allowedFields);
            }
        }

        // Mantener compatibilidad con filtros simples existentes
        $this->applySimpleFilters($query, $request, $allowedFields);

        return $query;
    }

    /**
     * Aplicar un grupo de filtros con operador lógico
     * 
     * @param Builder $query
     * @param array $group
     * @param array $allowedFields
     */
    protected function applyFilterGroup(Builder $query, array $group, array $allowedFields): void
    {
        $operator = strtoupper($group['operator'] ?? 'AND');
        $conditions = $group['conditions'] ?? [];
        $subgroups = $group['groups'] ?? [];

        if ($operator === 'OR') {
            $query->where(function ($q) use ($conditions, $subgroups, $allowedFields) {
                // Aplicar condiciones con OR
                foreach ($conditions as $condition) {
                    $q->orWhere(function ($subQ) use ($condition, $allowedFields) {
                        $this->applyCondition($subQ, $condition, $allowedFields);
                    });
                }

                // Aplicar subgrupos con OR
                foreach ($subgroups as $subgroup) {
                    $q->orWhere(function ($subQ) use ($subgroup, $allowedFields) {
                        $this->applyFilterGroup($subQ, $subgroup, $allowedFields);
                    });
                }
            });
        } else {
            // AND es el operador por defecto
            // Aplicar condiciones con AND
            foreach ($conditions as $condition) {
                $this->applyCondition($query, $condition, $allowedFields);
            }

            // Aplicar subgrupos con AND
            foreach ($subgroups as $subgroup) {
                $query->where(function ($q) use ($subgroup, $allowedFields) {
                    $this->applyFilterGroup($q, $subgroup, $allowedFields);
                });
            }
        }
    }

    /**
     * Aplicar una condición individual
     * 
     * @param Builder $query
     * @param array $condition
     * @param array $allowedFields
     */
    protected function applyCondition(Builder $query, array $condition, array $allowedFields): void
    {
        // Soportar ambas claves: 'field' y 'name' para compatibilidad
        $field = $condition['field'] ?? $condition['name'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        // Validar que el campo esté permitido
        if (!$field) {
            return;
        }
        
        // Si no se pasan campos permitidos o el campo está en la lista
        if (!empty($allowedFields) && !in_array($field, $allowedFields)) {
            return;
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
     * Aplicar filtros simples (para mantener compatibilidad)
     * 
     * @param Builder $query
     * @param Request $request
     * @param array $allowedFields
     */
    protected function applySimpleFilters(Builder $query, Request $request, array $allowedFields): void
    {
        // Este método puede ser sobrescrito en cada controlador
        // para mantener la compatibilidad con los filtros existentes
    }

    /**
     * Obtener configuración de campos para el frontend
     * 
     * @return array
     */
    public function getFilterFieldsConfig(): array
    {
        // Este método debe ser implementado en cada controlador
        // que use el trait para definir los campos disponibles
        return [];
    }

    /**
     * Validar que los filtros sean seguros
     * 
     * @param array $filters
     * @param array $allowedFields
     * @return bool
     */
    protected function validateFilters(array $filters, array $allowedFields): bool
    {
        // Validar estructura del filtro
        if (!isset($filters['operator']) || !in_array($filters['operator'], ['AND', 'OR'])) {
            return false;
        }

        // Validar condiciones
        if (isset($filters['conditions'])) {
            foreach ($filters['conditions'] as $condition) {
                if (!$this->validateCondition($condition, $allowedFields)) {
                    return false;
                }
            }
        }

        // Validar subgrupos recursivamente
        if (isset($filters['groups'])) {
            foreach ($filters['groups'] as $group) {
                if (!$this->validateFilters($group, $allowedFields)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validar una condición individual
     * 
     * @param array $condition
     * @param array $allowedFields
     * @return bool
     */
    protected function validateCondition(array $condition, array $allowedFields): bool
    {
        // Validar que tenga los campos requeridos
        if (!isset($condition['field']) || !isset($condition['operator'])) {
            return false;
        }

        // Validar que el campo esté permitido
        if (!in_array($condition['field'], $allowedFields)) {
            return false;
        }

        // Validar operador
        $validOperators = [
            'equals', 'not_equals', 'contains', 'not_contains',
            'starts_with', 'ends_with', 'is_empty', 'is_not_empty',
            'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal',
            'between', 'in', 'not_in'
        ];

        if (!in_array($condition['operator'], $validOperators)) {
            return false;
        }

        return true;
    }

    /**
     * Obtener resumen de filtros aplicados
     * 
     * @param Request $request
     * @return array
     */
    public function getAppliedFiltersSummary(Request $request): array
    {
        $summary = [];

        // Búsqueda rápida
        if ($request->filled('search')) {
            $summary['search'] = $request->search;
        }

        // Filtros avanzados
        if ($request->filled('advanced_filters')) {
            $filters = json_decode($request->advanced_filters, true);
            if ($filters) {
                $summary['advanced_filters'] = $this->summarizeFilterGroup($filters);
            }
        }

        return $summary;
    }

    /**
     * Resumir un grupo de filtros para mostrar al usuario
     * 
     * @param array $group
     * @return array
     */
    protected function summarizeFilterGroup(array $group): array
    {
        $summary = [
            'operator' => $group['operator'] ?? 'AND',
            'conditions_count' => count($group['conditions'] ?? []),
            'groups_count' => count($group['groups'] ?? []),
        ];

        if (isset($group['conditions'])) {
            $summary['conditions'] = array_map(function ($condition) {
                return [
                    'field' => $condition['field'] ?? '',
                    'operator' => $condition['operator'] ?? '',
                    'value' => $condition['value'] ?? null,
                ];
            }, $group['conditions']);
        }

        if (isset($group['groups'])) {
            $summary['groups'] = array_map(function ($subgroup) {
                return $this->summarizeFilterGroup($subgroup);
            }, $group['groups']);
        }

        return $summary;
    }
}