<?php

namespace App\Models;

use App\Traits\HasTenant;
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
        'model_type' => 'App\\Models\\User',
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
        
        $query = $modelClass::query();
        
        // Verificar si tenemos filtros para aplicar
        if (!empty($this->filters)) {
            // Simular request con los filtros del segmento
            $fakeRequest = new \Illuminate\Http\Request();
            
            // Los filtros están guardados como: { "advanced_filters": {...} }
            // Necesitamos pasar solo el contenido de advanced_filters
            $filtersToApply = $this->filters['advanced_filters'] ?? $this->filters;
            $fakeRequest->merge(['advanced_filters' => json_encode($filtersToApply)]);
            
            $controller = new class {
                use \App\Traits\HasAdvancedFilters;
            };
            
            // Definir campos permitidos según el modelo
            $allowedFields = $this->getAllowedFieldsForModel($modelClass);
            
            $query = $controller->applyAdvancedFilters($query, $fakeRequest, $allowedFields);
        }
        
        // Actualizar metadata
        $count = $query->count();
        $this->updateMetadata(['contacts_count' => $count]);
        
        return $query->get();
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
            'App\\Models\\User' => [
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
            'App\\Models\\Votacion' => [
                'titulo',
                'estado',
                'fecha_inicio',
                'fecha_fin',
                'categoria_id',
                'created_at'
            ],
            'App\\Models\\Convocatoria' => [
                'nombre',
                'estado',
                'fecha_apertura',
                'fecha_cierre',
                'cargo_id',
                'periodo_electoral_id'
            ],
            'App\\Models\\Postulacion' => [
                'estado',
                'convocatoria_id',
                'usuario_id',
                'created_at'
            ],
        ];
        
        return $fieldsByModel[$modelClass] ?? [];
    }
}