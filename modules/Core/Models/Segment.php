<?php

namespace Modules\Core\Models;

use Modules\Core\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Segment extends Model
{
    use HasFactory, HasTenant;

    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'model_type',
        'filters',
        'is_dynamic',
        'cache_duration',
        'metadata',
        'created_by'
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'filters' => 'array',
        'is_dynamic' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Valores por defecto para atributos
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'model_type' => 'Modules\Core\Models\User',
        'is_dynamic' => true,
        'cache_duration' => 300,
        'metadata' => '{}',
    ];

    /**
     * Obtener los roles asociados a este segmento
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_segments')
                    ->withTimestamps();
    }

    /**
     * Obtener el usuario que creó el segmento
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Evaluar el segmento y obtener los usuarios/registros
     */
    public function evaluate()
    {
        // Si es dinámico, usar cache
        if ($this->is_dynamic && $this->cache_duration > 0) {
            $cacheKey = "segment_{$this->id}_results";
            
            return Cache::remember($cacheKey, $this->cache_duration, function () {
                return $this->executeQuery();
            });
        }
        
        return $this->executeQuery();
    }

    /**
     * Ejecutar la query basada en los filtros
     */
    protected function executeQuery()
    {
        $modelClass = $this->model_type;
        
        if (!class_exists($modelClass)) {
            return collect();
        }
        
        // Usar withoutGlobalScopes para evitar conflictos con el trait HasTenant
        $query = $modelClass::withoutGlobalScopes()->where('tenant_id', $this->tenant_id);
        
        // Verificar si tenemos filtros para aplicar
        if (!empty($this->filters)) {
            try {
                $query = $this->applySegmentFilters($query, $modelClass);
                
            } catch (\Exception $e) {
                // Log del error pero continuar sin filtros en caso de fallo
                \Log::warning("Error aplicando filtros del segmento {$this->id}: " . $e->getMessage(), [
                    'segment_id' => $this->id,
                    'filters' => $this->filters,
                    'model_class' => $modelClass,
                    'exception_trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Actualizar metadata
        $count = $query->count();
        $this->updateMetadata(['contacts_count' => $count]);
        
        return $query->get();
    }

    /**
     * Aplicar filtros del segmento a la query
     */
    protected function applySegmentFilters($query, $modelClass)
    {
        // Extraer filtros avanzados
        $filtersToApply = $this->filters['advanced_filters'] ?? $this->filters;
        
        if (empty($filtersToApply) || !isset($filtersToApply['conditions'])) {
            return $query;
        }
        
        // Obtener campos permitidos
        $allowedFields = $this->getAllowedFieldsForModel($modelClass);
        
        if (empty($allowedFields)) {
            \Log::warning("No hay campos permitidos definidos para el modelo {$modelClass}");
            return $query;
        }
        
        // Aplicar filtros directamente sin fakeRequest
        $operator = strtoupper($filtersToApply['operator'] ?? 'AND');
        $conditions = $filtersToApply['conditions'] ?? [];
        
        // Aplicar condiciones
        if ($operator === 'OR') {
            $query->where(function ($q) use ($conditions, $allowedFields) {
                foreach ($conditions as $index => $condition) {
                    $q->orWhere(function ($subQ) use ($condition, $allowedFields, $index) {
                        $this->applySegmentCondition($subQ, $condition, $allowedFields, $index);
                    });
                }
            });
        } else {
            // AND (default)
            foreach ($conditions as $index => $condition) {
                $this->applySegmentCondition($query, $condition, $allowedFields, $index);
            }
        }
        
        return $query;
    }

    /**
     * Aplicar una condición individual del segmento
     */
    protected function applySegmentCondition($query, $condition, $allowedFields, $index = 0)
    {
        $field = $condition['field'] ?? $condition['name'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        if (!$field || !in_array($field, $allowedFields)) {
            \Log::warning("Campo no permitido o vacío en segmento {$this->id}", [
                'field' => $field,
                'allowed_fields' => $allowedFields,
                'condition_index' => $index
            ]);
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
                    $q->whereNull($field)->orWhere($field, '=', '');
                });
                break;
                
            case 'is_not_empty':
                $query->where(function ($q) use ($field) {
                    $q->whereNotNull($field)->where($field, '!=', '');
                });
                break;
                
            case 'greater_than':
                $query->where($field, '>', $value);
                break;
                
            case 'less_than':
                $query->where($field, '<', $value);
                break;
                
            default:
                \Log::warning("Operador desconocido en segmento {$this->id}: {$operator}");
                return;
        }
    }

    /**
     * Actualizar metadata del segmento
     */
    public function updateMetadata(array $data): void
    {
        $metadata = $this->metadata ?? [];
        $metadata = array_merge($metadata, $data);
        $metadata['last_calculated_at'] = now()->toDateTimeString();
        
        $this->metadata = $metadata;
        $this->save();
    }

    /**
     * Limpiar cache del segmento
     */
    public function clearCache(): void
    {
        $cacheKey = "segment_{$this->id}_results";
        Cache::forget($cacheKey);
    }

    /**
     * Recalcular el segmento
     */
    public function recalculate()
    {
        $this->clearCache();
        return $this->evaluate();
    }

    /**
     * Obtener el conteo de registros sin evaluar todo
     */
    public function getCount(): int
    {
        $metadata = $this->metadata ?? [];
        
        // Si tenemos un conteo reciente en metadata, usarlo
        if (isset($metadata['contacts_count']) && isset($metadata['last_calculated_at'])) {
            $lastCalculated = \Carbon\Carbon::parse($metadata['last_calculated_at']);
            
            // Si el cálculo es reciente (menos de 1 hora), usar el cache
            if ($lastCalculated->diffInMinutes(now()) < 60) {
                return $metadata['contacts_count'];
            }
        }
        
        // Recalcular
        $this->evaluate();
        
        return $this->metadata['contacts_count'] ?? 0;
    }

    /**
     * Scope para segmentos activos/dinámicos
     */
    public function scopeDynamic($query)
    {
        return $query->where('is_dynamic', true);
    }

    /**
     * Scope para segmentos estáticos
     */
    public function scopeStatic($query)
    {
        return $query->where('is_dynamic', false);
    }

    /**
     * Obtener campos permitidos para filtrar según el modelo
     */
    protected function getAllowedFieldsForModel($modelClass): array
    {
        // Definir campos permitidos para cada modelo
        $fieldsByModel = [
            'Modules\Core\Models\User' => [
                'name', 
                'email', 
                'activo', 
                'created_at', 
                'territorio_id', 
                'departamento_id', 
                'municipio_id',
                'localidad_id',
                'role'
            ],
            'Modules\Votaciones\Models\Votacion' => [
                'titulo',
                'estado',
                'fecha_inicio',
                'fecha_fin',
                'categoria_id',
                'created_at'
            ],
            'Modules\Elecciones\Models\Convocatoria' => [
                'nombre',
                'estado',
                'fecha_apertura',
                'fecha_cierre',
                'cargo_id',
                'periodo_electoral_id'
            ],
            'Modules\Elecciones\Models\Postulacion' => [
                'estado',
                'convocatoria_id',
                'usuario_id',
                'created_at'
            ],
        ];
        
        return $fieldsByModel[$modelClass] ?? [];
    }
}