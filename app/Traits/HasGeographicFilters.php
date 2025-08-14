<?php

namespace App\Traits;

use App\Models\Territorio;
use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Localidad;

/**
 * Trait para añadir filtros geográficos en cascada a los controladores
 * Este trait es agnóstico y puede ser utilizado en cualquier controlador
 * que necesite implementar filtros territoriales
 */
trait HasGeographicFilters
{
    /**
     * Obtener la configuración de campos geográficos para filtros en cascada
     * 
     * @param array $options Opciones de configuración
     * @return array
     */
    public function getGeographicFilterFields(array $options = []): array
    {
        $prefix = $options['prefix'] ?? '';
        $required = $options['required'] ?? false;
        $includeTerritory = $options['include_territory'] ?? true;
        $includeDepartment = $options['include_department'] ?? true;
        $includeMunicipality = $options['include_municipality'] ?? true;
        $includeLocality = $options['include_locality'] ?? true;
        
        $fields = [];
        
        // Campo Territorio
        if ($includeTerritory) {
            $fields[] = [
                'name' => $prefix . 'territorio_id',
                'label' => 'Territorio',
                'type' => 'select-cascade',
                'required' => $required,
                'placeholder' => 'Seleccionar territorio...',
                'cascadeEndpoint' => route('admin.geographic.territorios'),
                'cascadeChildren' => $includeDepartment ? [$prefix . 'departamento_id'] : [],
                'options' => [], // Se cargarán dinámicamente
                'operators' => ['equals', 'not_equals', 'is_empty', 'is_not_empty'],
            ];
        }
        
        // Campo Departamento
        if ($includeDepartment) {
            $fields[] = [
                'name' => $prefix . 'departamento_id',
                'label' => 'Departamento',
                'type' => 'select-cascade',
                'required' => $required && !$includeTerritory,
                'placeholder' => 'Seleccionar departamento...',
                'cascadeFrom' => $includeTerritory ? $prefix . 'territorio_id' : null,
                'cascadeEndpoint' => route('admin.geographic.departamentos'),
                'cascadeParam' => 'territorio_ids',
                'cascadeChildren' => $includeMunicipality ? [$prefix . 'municipio_id'] : [],
                'options' => [], // Se cargarán dinámicamente
                'operators' => ['equals', 'not_equals', 'is_empty', 'is_not_empty'],
            ];
        }
        
        // Campo Municipio
        if ($includeMunicipality) {
            $fields[] = [
                'name' => $prefix . 'municipio_id',
                'label' => 'Municipio',
                'type' => 'select-cascade',
                'required' => $required && !$includeDepartment,
                'placeholder' => 'Seleccionar municipio...',
                'cascadeFrom' => $includeDepartment ? $prefix . 'departamento_id' : null,
                'cascadeEndpoint' => route('admin.geographic.municipios'),
                'cascadeParam' => 'departamento_ids',
                'cascadeChildren' => $includeLocality ? [$prefix . 'localidad_id'] : [],
                'options' => [], // Se cargarán dinámicamente
                'operators' => ['equals', 'not_equals', 'is_empty', 'is_not_empty'],
            ];
        }
        
        // Campo Localidad
        if ($includeLocality) {
            $fields[] = [
                'name' => $prefix . 'localidad_id',
                'label' => 'Localidad',
                'type' => 'select-cascade',
                'required' => $required && !$includeMunicipality,
                'placeholder' => 'Seleccionar localidad...',
                'cascadeFrom' => $includeMunicipality ? $prefix . 'municipio_id' : null,
                'cascadeEndpoint' => route('admin.geographic.localidades'),
                'cascadeParam' => 'municipio_ids',
                'cascadeChildren' => [],
                'options' => [], // Se cargarán dinámicamente
                'operators' => ['equals', 'not_equals', 'is_empty', 'is_not_empty'],
            ];
        }
        
        return $fields;
    }
    
    /**
     * Obtener la configuración de campos geográficos para filtros de usuarios
     * Esta versión utiliza los campos geográficos directamente de la tabla users
     * 
     * @param array $options Opciones de configuración
     * @return array
     */
    public function getUserGeographicFilterFields(array $options = []): array
    {
        // Para usuarios, los campos no tienen prefijo ya que están directamente en la tabla
        $options['prefix'] = '';
        return $this->getGeographicFilterFields($options);
    }
    
    /**
     * Obtener la configuración de campos geográficos para filtros de relaciones
     * Esta versión es útil cuando se filtran entidades relacionadas
     * 
     * @param string $relation Nombre de la relación (ej: 'participantes')
     * @param array $options Opciones adicionales
     * @return array
     */
    public function getRelationGeographicFilterFields(string $relation, array $options = []): array
    {
        $fields = $this->getGeographicFilterFields($options);
        
        // Añadir prefijo de relación a cada campo
        foreach ($fields as &$field) {
            $field['name'] = $relation . '.' . $field['name'];
            
            // Actualizar referencias en cascada
            if (isset($field['cascadeFrom'])) {
                $field['cascadeFrom'] = $relation . '.' . $field['cascadeFrom'];
            }
            
            if (isset($field['cascadeChildren']) && is_array($field['cascadeChildren'])) {
                $field['cascadeChildren'] = array_map(function($child) use ($relation) {
                    return $relation . '.' . $child;
                }, $field['cascadeChildren']);
            }
        }
        
        return $fields;
    }
    
    /**
     * Aplicar filtros geográficos a una consulta
     * Útil cuando se necesita aplicar filtros territoriales manualmente
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters Array con territorio_id, departamento_id, etc.
     * @param string $prefix Prefijo para los campos (opcional)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyGeographicFilters($query, array $filters, string $prefix = '')
    {
        // Filtrar por territorio
        if (!empty($filters[$prefix . 'territorio_id'])) {
            $query->where($prefix . 'territorio_id', $filters[$prefix . 'territorio_id']);
        }
        
        // Filtrar por departamento
        if (!empty($filters[$prefix . 'departamento_id'])) {
            $query->where($prefix . 'departamento_id', $filters[$prefix . 'departamento_id']);
        }
        
        // Filtrar por municipio
        if (!empty($filters[$prefix . 'municipio_id'])) {
            $query->where($prefix . 'municipio_id', $filters[$prefix . 'municipio_id']);
        }
        
        // Filtrar por localidad
        if (!empty($filters[$prefix . 'localidad_id'])) {
            $query->where($prefix . 'localidad_id', $filters[$prefix . 'localidad_id']);
        }
        
        return $query;
    }
    
    /**
     * Cargar opciones iniciales para campos geográficos
     * Útil cuando se edita un registro y se necesitan cargar las opciones seleccionadas
     * 
     * @param mixed $model Modelo con campos geográficos
     * @return array
     */
    public function loadGeographicOptions($model): array
    {
        $options = [];
        
        // Cargar territorio si existe
        if ($model->territorio_id) {
            $options['territorio'] = Territorio::find($model->territorio_id);
        }
        
        // Cargar departamento si existe
        if ($model->departamento_id) {
            $options['departamento'] = Departamento::find($model->departamento_id);
            
            // Cargar todos los departamentos del territorio
            if ($model->territorio_id) {
                $options['departamentos'] = Departamento::where('territorio_id', $model->territorio_id)
                    ->activos()
                    ->get(['id', 'nombre']);
            }
        }
        
        // Cargar municipio si existe
        if ($model->municipio_id) {
            $options['municipio'] = Municipio::find($model->municipio_id);
            
            // Cargar todos los municipios del departamento
            if ($model->departamento_id) {
                $options['municipios'] = Municipio::where('departamento_id', $model->departamento_id)
                    ->activos()
                    ->get(['id', 'nombre']);
            }
        }
        
        // Cargar localidad si existe
        if ($model->localidad_id) {
            $options['localidad'] = Localidad::find($model->localidad_id);
            
            // Cargar todas las localidades del municipio
            if ($model->municipio_id) {
                $options['localidades'] = Localidad::where('municipio_id', $model->municipio_id)
                    ->activos()
                    ->get(['id', 'nombre']);
            }
        }
        
        return $options;
    }
}