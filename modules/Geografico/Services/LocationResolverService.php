<?php

namespace Modules\Geografico\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LocationResolverService
{
    /**
     * Transformar nombres de ubicaciones a IDs respetando la jerarquía
     * territorio → departamento → municipio → localidad
     */
    public function resolveLocationIds(array $data): array
    {
        $resolvedData = $data;
        
        // Convertir strings vacíos a null para campos de ubicación
        $locationFields = ['territorio_id', 'departamento_id', 'municipio_id', 'localidad_id'];
        foreach ($locationFields as $field) {
            if (isset($resolvedData[$field]) && trim((string)$resolvedData[$field]) === '') {
                $resolvedData[$field] = null;
            }
        }
        
        // Resolver territorio_id
        if (isset($data['territorio_id']) && $this->isStringValue($data['territorio_id'])) {
            $resolvedData['territorio_id'] = $this->resolveTerritorioId($data['territorio_id']);
        }
        
        // Resolver departamento_id (necesita territorio_id)
        if (isset($data['departamento_id']) && $this->isStringValue($data['departamento_id'])) {
            $territorioId = isset($resolvedData['territorio_id']) && is_numeric($resolvedData['territorio_id']) 
                ? (int) $resolvedData['territorio_id'] 
                : null;
            $resolvedData['departamento_id'] = $this->resolveDepartamentoId($data['departamento_id'], $territorioId);
        }
        
        // Resolver municipio_id (necesita departamento_id)
        if (isset($data['municipio_id']) && $this->isStringValue($data['municipio_id'])) {
            $departamentoId = isset($resolvedData['departamento_id']) && is_numeric($resolvedData['departamento_id']) 
                ? (int) $resolvedData['departamento_id'] 
                : null;
            $resolvedData['municipio_id'] = $this->resolveMunicipioId($data['municipio_id'], $departamentoId);
        }
        
        // Resolver localidad_id (necesita municipio_id)
        if (isset($data['localidad_id']) && $this->isStringValue($data['localidad_id'])) {
            $municipioId = isset($resolvedData['municipio_id']) && is_numeric($resolvedData['municipio_id']) 
                ? (int) $resolvedData['municipio_id'] 
                : null;
            $resolvedData['localidad_id'] = $this->resolveLocalidadId($data['localidad_id'], $municipioId);
        }
        
        return $resolvedData;
    }
    
    /**
     * Resolver territorio por nombre
     */
    private function resolveTerritorioId(string $nombre): ?int
    {
        $cacheKey = "territorio_by_name_" . md5($nombre);
        
        return Cache::remember($cacheKey, 3600, function () use ($nombre) {
            $territorio = DB::table('territorios')
                ->where('nombre', 'LIKE', '%' . trim($nombre) . '%')
                ->where('activo', true)
                ->first();
            
            if ($territorio) {
                Log::info("Territorio resuelto: '{$nombre}' → ID {$territorio->id}");
                return $territorio->id;
            }
            
            Log::warning("Territorio no encontrado: '{$nombre}'");
            return null;
        });
    }
    
    /**
     * Resolver departamento por nombre dentro de un territorio
     */
    private function resolveDepartamentoId(string $nombre, ?int $territorioId): ?int
    {
        // Validación robusta del territorioId
        if (!$territorioId || !is_numeric($territorioId) || $territorioId <= 0) {
            Log::warning("No se puede resolver departamento '{$nombre}' sin territorio_id válido (recibido: " . var_export($territorioId, true) . ")");
            return null;
        }
        
        // Asegurar que territorioId sea entero
        $territorioId = (int) $territorioId;
        
        $cacheKey = "departamento_by_name_{$territorioId}_" . md5($nombre);
        
        return Cache::remember($cacheKey, 3600, function () use ($nombre, $territorioId) {
            $departamento = DB::table('departamentos')
                ->where('nombre', 'LIKE', '%' . trim($nombre) . '%')
                ->where('territorio_id', $territorioId)
                ->where('activo', true)
                ->first();
            
            if ($departamento) {
                Log::info("Departamento resuelto: '{$nombre}' (territorio {$territorioId}) → ID {$departamento->id}");
                return $departamento->id;
            }
            
            Log::warning("Departamento no encontrado: '{$nombre}' en territorio {$territorioId}");
            return null;
        });
    }
    
    /**
     * Resolver municipio por nombre dentro de un departamento
     */
    private function resolveMunicipioId(string $nombre, ?int $departamentoId): ?int
    {
        // Validación robusta del departamentoId
        if (!$departamentoId || !is_numeric($departamentoId) || $departamentoId <= 0) {
            Log::warning("No se puede resolver municipio '{$nombre}' sin departamento_id válido (recibido: " . var_export($departamentoId, true) . ")");
            return null;
        }
        
        // Asegurar que departamentoId sea entero
        $departamentoId = (int) $departamentoId;
        
        $cacheKey = "municipio_by_name_{$departamentoId}_" . md5($nombre);
        
        return Cache::remember($cacheKey, 3600, function () use ($nombre, $departamentoId) {
            $municipio = DB::table('municipios')
                ->where('nombre', 'LIKE', '%' . trim($nombre) . '%')
                ->where('departamento_id', $departamentoId)
                ->where('activo', true)
                ->first();
            
            if ($municipio) {
                Log::info("Municipio resuelto: '{$nombre}' (departamento {$departamentoId}) → ID {$municipio->id}");
                return $municipio->id;
            }
            
            Log::warning("Municipio no encontrado: '{$nombre}' en departamento {$departamentoId}");
            return null;
        });
    }
    
    /**
     * Resolver localidad por nombre dentro de un municipio
     */
    private function resolveLocalidadId(string $nombre, ?int $municipioId): ?int
    {
        // Validación robusta del municipioId
        if (!$municipioId || !is_numeric($municipioId) || $municipioId <= 0) {
            Log::warning("No se puede resolver localidad '{$nombre}' sin municipio_id válido (recibido: " . var_export($municipioId, true) . ")");
            return null;
        }
        
        // Asegurar que municipioId sea entero
        $municipioId = (int) $municipioId;
        
        $cacheKey = "localidad_by_name_{$municipioId}_" . md5($nombre);
        
        return Cache::remember($cacheKey, 3600, function () use ($nombre, $municipioId) {
            $localidad = DB::table('localidades')
                ->where('nombre', 'LIKE', '%' . trim($nombre) . '%')
                ->where('municipio_id', $municipioId)
                ->where('activo', true)
                ->first();
            
            if ($localidad) {
                Log::info("Localidad resuelta: '{$nombre}' (municipio {$municipioId}) → ID {$localidad->id}");
                return $localidad->id;
            }
            
            Log::warning("Localidad no encontrada: '{$nombre}' en municipio {$municipioId}");
            return null;
        });
    }
    
    /**
     * Verificar si un valor es string (nombre) en lugar de entero (ID)
     */
    private function isStringValue($value): bool
    {
        return is_string($value) && !is_numeric($value) && !empty(trim($value));
    }
    
    /**
     * Obtener nombres de ubicaciones para mostrar en frontend
     */
    public function getLocationNames(array $data): array
    {
        $names = [];
        
        if (isset($data['territorio_id']) && is_numeric($data['territorio_id'])) {
            $names['territorio_nombre'] = $this->getTerritorioName($data['territorio_id']);
        }
        
        if (isset($data['departamento_id']) && is_numeric($data['departamento_id'])) {
            $names['departamento_nombre'] = $this->getDepartamentoName($data['departamento_id']);
        }
        
        if (isset($data['municipio_id']) && is_numeric($data['municipio_id'])) {
            $names['municipio_nombre'] = $this->getMunicipioName($data['municipio_id']);
        }
        
        if (isset($data['localidad_id']) && is_numeric($data['localidad_id'])) {
            $names['localidad_nombre'] = $this->getLocalidadName($data['localidad_id']);
        }
        
        return $names;
    }
    
    /**
     * Obtener nombre de territorio por ID
     */
    private function getTerritorioName(int $id): ?string
    {
        $cacheKey = "territorio_name_{$id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($id) {
            return DB::table('territorios')->where('id', $id)->value('nombre');
        });
    }
    
    /**
     * Obtener nombre de departamento por ID
     */
    private function getDepartamentoName(int $id): ?string
    {
        $cacheKey = "departamento_name_{$id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($id) {
            return DB::table('departamentos')->where('id', $id)->value('nombre');
        });
    }
    
    /**
     * Obtener nombre de municipio por ID
     */
    private function getMunicipioName(int $id): ?string
    {
        $cacheKey = "municipio_name_{$id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($id) {
            return DB::table('municipios')->where('id', $id)->value('nombre');
        });
    }
    
    /**
     * Obtener nombre de localidad por ID
     */
    private function getLocalidadName(int $id): ?string
    {
        $cacheKey = "localidad_name_{$id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($id) {
            return DB::table('localidades')->where('id', $id)->value('nombre');
        });
    }
    
    /**
     * Validar que las ubicaciones respeten la jerarquía
     */
    public function validateLocationHierarchy(array $data): array
    {
        $errors = [];
        
        // Si hay departamento_id, debe haber territorio_id
        if (!empty($data['departamento_id']) && empty($data['territorio_id'])) {
            $errors[] = 'Si se especifica departamento, debe especificarse también territorio';
        }
        
        // Si hay municipio_id, debe haber departamento_id
        if (!empty($data['municipio_id']) && empty($data['departamento_id'])) {
            $errors[] = 'Si se especifica municipio, debe especificarse también departamento';
        }
        
        // Si hay localidad_id, debe haber municipio_id
        if (!empty($data['localidad_id']) && empty($data['municipio_id'])) {
            $errors[] = 'Si se especifica localidad, debe especificarse también municipio';
        }
        
        // Validar que los IDs pertenezcan a sus respectivos padres (solo si ambos valores son enteros válidos)
        if (!empty($data['departamento_id']) && !empty($data['territorio_id']) && 
            is_numeric($data['departamento_id']) && is_numeric($data['territorio_id'])) {
            $departamento = DB::table('departamentos')
                ->where('id', $data['departamento_id'])
                ->where('territorio_id', $data['territorio_id'])
                ->first();
                
            if (!$departamento) {
                $errors[] = 'El departamento especificado no pertenece al territorio indicado';
            }
        }
        
        if (!empty($data['municipio_id']) && !empty($data['departamento_id']) && 
            is_numeric($data['municipio_id']) && is_numeric($data['departamento_id'])) {
            $municipio = DB::table('municipios')
                ->where('id', $data['municipio_id'])
                ->where('departamento_id', $data['departamento_id'])
                ->first();
                
            if (!$municipio) {
                $errors[] = 'El municipio especificado no pertenece al departamento indicado';
            }
        }
        
        if (!empty($data['localidad_id']) && !empty($data['municipio_id']) && 
            is_numeric($data['localidad_id']) && is_numeric($data['municipio_id'])) {
            $localidad = DB::table('localidades')
                ->where('id', $data['localidad_id'])
                ->where('municipio_id', $data['municipio_id'])
                ->first();
                
            if (!$localidad) {
                $errors[] = 'La localidad especificada no pertenece al municipio indicado';
            }
        }
        
        return $errors;
    }
}