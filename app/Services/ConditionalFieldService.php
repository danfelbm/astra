<?php

namespace App\Services;

class ConditionalFieldService
{
    /**
     * Evalúa si un campo debe mostrarse basándose en sus condiciones
     */
    public function shouldShowField(array $field, array $formData): bool
    {
        // Si no tiene configuración condicional o no está habilitada, siempre mostrar
        if (!isset($field['conditionalConfig']) || !$field['conditionalConfig']['enabled']) {
            return true;
        }

        $config = $field['conditionalConfig'];
        $conditions = $config['conditions'] ?? [];
        
        // Si no hay condiciones, mostrar el campo
        if (empty($conditions)) {
            return true;
        }

        // Evaluar cada condición
        $results = array_map(function ($condition) use ($formData) {
            return $this->evaluateCondition(
                $formData[$condition['fieldId']] ?? null,
                $condition['operator'],
                $condition['value'] ?? null
            );
        }, $conditions);

        // Aplicar lógica AND/OR
        $showWhen = $config['showWhen'] ?? 'all';
        
        if ($showWhen === 'all') {
            // Todas las condiciones deben cumplirse
            return !in_array(false, $results, true);
        } else {
            // Al menos una condición debe cumplirse
            return in_array(true, $results, true);
        }
    }

    /**
     * Evalúa una condición individual
     */
    private function evaluateCondition($sourceValue, string $operator, $targetValue): bool
    {
        // Normalizar valores para comparación consistente
        $normalizeValue = function ($val): string {
            if ($val === null || $val === '') {
                return '';
            }
            if (is_bool($val)) {
                return $val ? 'true' : 'false';
            }
            return (string) $val;
        };

        switch ($operator) {
            case 'equals':
                // Para arrays, verificar si contiene exactamente los mismos valores
                if (is_array($sourceValue) && is_array($targetValue)) {
                    return count($sourceValue) === count($targetValue) &&
                           !array_diff($sourceValue, $targetValue);
                }
                return $normalizeValue($sourceValue) === $normalizeValue($targetValue);
            
            case 'not_equals':
                if (is_array($sourceValue) && is_array($targetValue)) {
                    return count($sourceValue) !== count($targetValue) ||
                           !empty(array_diff($sourceValue, $targetValue));
                }
                return $normalizeValue($sourceValue) !== $normalizeValue($targetValue);
            
            case 'contains':
                // Para arrays, verificar si contiene el valor
                if (is_array($sourceValue)) {
                    if (is_array($targetValue)) {
                        return !empty(array_intersect($sourceValue, $targetValue));
                    }
                    return in_array($targetValue, $sourceValue);
                }
                // Para strings, verificar si contiene el texto
                return str_contains($normalizeValue($sourceValue), $normalizeValue($targetValue));
            
            case 'not_contains':
                if (is_array($sourceValue)) {
                    if (is_array($targetValue)) {
                        return empty(array_intersect($sourceValue, $targetValue));
                    }
                    return !in_array($targetValue, $sourceValue);
                }
                return !str_contains($normalizeValue($sourceValue), $normalizeValue($targetValue));
            
            case 'is_empty':
                if (is_array($sourceValue)) {
                    return empty($sourceValue);
                }
                return empty($sourceValue) || $normalizeValue($sourceValue) === '';
            
            case 'is_not_empty':
                if (is_array($sourceValue)) {
                    return !empty($sourceValue);
                }
                return !empty($sourceValue) && $normalizeValue($sourceValue) !== '';
            
            default:
                \Log::warning("Operador condicional no soportado: {$operator}");
                return true;
        }
    }

    /**
     * Obtiene solo los campos visibles según las condiciones
     */
    public function getVisibleFields(array $fields, array $formData): array
    {
        return array_filter($fields, function ($field) use ($formData) {
            return $this->shouldShowField($field, $formData);
        });
    }

    /**
     * Valida que no haya referencias circulares en las condiciones
     */
    public function validateConditionalRules(array $fields): array
    {
        $errors = [];
        
        foreach ($fields as $field) {
            if (!isset($field['conditionalConfig']) || !$field['conditionalConfig']['enabled']) {
                continue;
            }

            $fieldId = $field['id'];
            $conditions = $field['conditionalConfig']['conditions'] ?? [];

            // Verificar auto-referencia
            foreach ($conditions as $condition) {
                if ($condition['fieldId'] === $fieldId) {
                    $errors[] = "El campo \"{$field['title']}\" no puede depender de sí mismo";
                }

                // Verificar que el campo fuente existe
                $sourceField = null;
                foreach ($fields as $f) {
                    if ($f['id'] === $condition['fieldId']) {
                        $sourceField = $f;
                        break;
                    }
                }

                if (!$sourceField) {
                    $errors[] = "El campo \"{$field['title']}\" hace referencia a un campo inexistente: {$condition['fieldId']}";
                } else {
                    // Verificar que el campo fuente sea de tipo válido
                    $validTypes = ['select', 'radio', 'checkbox'];
                    if (!in_array($sourceField['type'], $validTypes)) {
                        $errors[] = "El campo \"{$field['title']}\" depende de \"{$sourceField['title']}\" que no es de tipo select, radio o checkbox";
                    }
                }
            }
        }

        // Detectar referencias circulares más complejas
        $visited = [];
        $checkCircular = function ($fieldId, $path = []) use ($fields, &$checkCircular, &$errors, &$visited) {
            if (in_array($fieldId, $path)) {
                $errors[] = "Referencia circular detectada: " . implode(' → ', array_merge($path, [$fieldId]));
                return true;
            }

            if (in_array($fieldId, $visited)) {
                return false;
            }
            $visited[] = $fieldId;

            $currentField = null;
            foreach ($fields as $f) {
                if ($f['id'] === $fieldId) {
                    $currentField = $f;
                    break;
                }
            }

            if (!$currentField || !isset($currentField['conditionalConfig']) || 
                !$currentField['conditionalConfig']['enabled']) {
                return false;
            }

            foreach ($currentField['conditionalConfig']['conditions'] as $condition) {
                $checkCircular($condition['fieldId'], array_merge($path, [$fieldId]));
            }

            return false;
        };

        foreach ($fields as $field) {
            if (isset($field['conditionalConfig']) && $field['conditionalConfig']['enabled']) {
                $checkCircular($field['id']);
            }
        }

        return $errors;
    }
}