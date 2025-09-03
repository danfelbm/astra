<?php

namespace Modules\Core\Traits;

use Modules\Core\Services\IpAddressService;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasAuditLog
 * 
 * Implementación agnóstica de auditoría usando Spatie Activity Log
 * Puede ser usado por cualquier modelo que requiera auditoría completa
 */
trait HasAuditLog
{
    use LogsActivity;

    /**
     * Configurar opciones de logging para Spatie Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Registrar todos los cambios de atributos
            ->logOnlyDirty() // Solo registrar campos que cambiaron
            ->dontSubmitEmptyLogs() // No crear logs si no hay cambios
            ->setDescriptionForEvent(fn(string $eventName) => $this->getEventDescription($eventName))
            ->useLogName($this->getLogName()); // Usar nombre de log personalizado
    }

    /**
     * Obtener el nombre del log basado en el modelo
     * Se puede sobrescribir en el modelo si se necesita un nombre específico
     */
    protected function getLogName(): string
    {
        // Por defecto, usar el nombre de la tabla del modelo
        return property_exists($this, 'auditLogName') 
            ? $this->auditLogName 
            : $this->getTable();
    }

    /**
     * Generar descripción del evento basado en el tipo
     * Se puede sobrescribir en el modelo para personalizaciones
     */
    protected function getEventDescription(string $eventName): string
    {
        $modelName = class_basename($this);
        $userName = Auth::user() ? Auth::user()->name : 'Sistema';
        
        return match($eventName) {
            'created' => "{$userName} creó {$modelName}",
            'updated' => "{$userName} actualizó {$modelName}",
            'deleted' => "{$userName} eliminó {$modelName}",
            'restored' => "{$userName} restauró {$modelName}",
            default => "{$userName} realizó {$eventName} en {$modelName}"
        };
    }

    /**
     * Registrar una actividad personalizada con contexto adicional
     * 
     * @param string $description Descripción de la actividad
     * @param array $properties Propiedades adicionales para registrar
     * @param string|null $event Nombre del evento (opcional)
     */
    public function logCustomActivity(string $description, array $properties = [], ?string $event = null): void
    {
        activity($this->getLogName())
            ->performedOn($this)
            ->causedBy(Auth::user())
            ->withProperties($this->enrichProperties($properties))
            ->event($event ?? 'custom')
            ->log($description);
    }

    /**
     * Registrar una acción específica con descripción detallada
     * 
     * @param string $action Nombre de la acción (ej: 'aprobado', 'rechazado')
     * @param string|null $details Detalles adicionales
     * @param array $extraData Datos adicionales para registrar
     */
    public function logAction(string $action, ?string $details = null, array $extraData = []): void
    {
        $userName = Auth::user() ? Auth::user()->name : 'Sistema';
        $modelName = class_basename($this);
        $modelId = $this->getKey();
        
        $description = "{$userName} {$action} {$modelName} #{$modelId}";
        if ($details) {
            $description .= " - {$details}";
        }
        
        $properties = array_merge([
            'action' => $action,
            'model_id' => $modelId,
            'model_type' => get_class($this),
        ], $extraData);
        
        $this->logCustomActivity($description, $properties, $action);
    }

    /**
     * Registrar cambio de estado
     * 
     * @param string $field Campo que cambió
     * @param mixed $oldValue Valor anterior
     * @param mixed $newValue Nuevo valor
     * @param string|null $reason Razón del cambio
     */
    public function logStateChange(string $field, $oldValue, $newValue, ?string $reason = null): void
    {
        $userName = Auth::user() ? Auth::user()->name : 'Sistema';
        $modelName = class_basename($this);
        
        $description = "{$userName} cambió {$field} de {$modelName} de '{$oldValue}' a '{$newValue}'";
        if ($reason) {
            $description .= " - Razón: {$reason}";
        }
        
        $properties = [
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
        ];
        
        $this->logCustomActivity($description, $properties, 'state_changed');
    }

    /**
     * Registrar acceso o visualización
     * 
     * @param string|null $context Contexto del acceso
     */
    public function logAccess(?string $context = null): void
    {
        $userName = Auth::user() ? Auth::user()->name : 'Sistema';
        $modelName = class_basename($this);
        $modelId = $this->getKey();
        
        $description = "{$userName} accedió a {$modelName} #{$modelId}";
        if ($context) {
            $description .= " - {$context}";
        }
        
        $properties = [
            'action' => 'accessed',
            'context' => $context,
        ];
        
        activity($this->getLogName())
            ->performedOn($this)
            ->causedBy(Auth::user())
            ->withProperties($this->enrichProperties($properties))
            ->event('accessed')
            ->log($description);
    }

    /**
     * Registrar intento de acción no autorizada
     * 
     * @param string $action Acción intentada
     * @param string|null $reason Razón del bloqueo
     */
    public function logUnauthorizedAttempt(string $action, ?string $reason = null): void
    {
        $userName = Auth::user() ? Auth::user()->name : 'Usuario no autenticado';
        $modelName = class_basename($this);
        $modelId = $this->getKey();
        
        $description = "⚠️ {$userName} intentó {$action} en {$modelName} #{$modelId} sin autorización";
        if ($reason) {
            $description .= " - {$reason}";
        }
        
        $properties = [
            'action_attempted' => $action,
            'reason' => $reason,
            'blocked' => true,
        ];
        
        activity($this->getLogName())
            ->performedOn($this)
            ->causedBy(Auth::user())
            ->withProperties($this->enrichProperties($properties))
            ->event('unauthorized')
            ->log($description);
    }

    /**
     * Registrar exportación de datos
     * 
     * @param string $format Formato de exportación
     * @param int|null $recordCount Cantidad de registros exportados
     */
    public function logExport(string $format, ?int $recordCount = null): void
    {
        $userName = Auth::user() ? Auth::user()->name : 'Sistema';
        $modelName = class_basename($this);
        
        $description = "{$userName} exportó datos de {$modelName} en formato {$format}";
        if ($recordCount !== null) {
            $description .= " ({$recordCount} registros)";
        }
        
        $properties = [
            'action' => 'exported',
            'format' => $format,
            'record_count' => $recordCount,
        ];
        
        $this->logCustomActivity($description, $properties, 'exported');
    }

    /**
     * Registrar envío de notificación
     * 
     * @param string $type Tipo de notificación
     * @param string $recipient Destinatario
     * @param bool $success Si se envió exitosamente
     */
    public function logNotification(string $type, string $recipient, bool $success = true): void
    {
        $userName = Auth::user() ? Auth::user()->name : 'Sistema';
        $modelName = class_basename($this);
        $modelId = $this->getKey();
        
        $status = $success ? 'exitosamente' : 'con error';
        $description = "{$userName} envió notificación {$type} {$status} para {$modelName} #{$modelId} a {$recipient}";
        
        $properties = [
            'notification_type' => $type,
            'recipient' => $recipient,
            'success' => $success,
        ];
        
        $this->logCustomActivity($description, $properties, 'notification_sent');
    }

    /**
     * Enriquecer propiedades con información del contexto
     * 
     * @param array $properties Propiedades base
     * @return array Propiedades enriquecidas
     */
    protected function enrichProperties(array $properties): array
    {
        // Agregar información del request si está disponible
        if (request()) {
            $properties['ip'] = IpAddressService::getRealIp(request());
            $properties['user_agent'] = request()->userAgent();
            $properties['url'] = request()->fullUrl();
            $properties['method'] = request()->method();
        }
        
        // Agregar timestamp
        $properties['timestamp'] = now()->toISOString();
        
        // Agregar tenant_id si el modelo usa HasTenant
        if (in_array('App\Traits\HasTenant', class_uses($this))) {
            $properties['tenant_id'] = $this->tenant_id;
        }
        
        return $properties;
    }

    /**
     * Obtener logs de actividad del modelo
     * 
     * @param int|null $limit Límite de registros
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActivityLogs(?int $limit = null)
    {
        $query = \Spatie\Activitylog\Models\Activity::where('subject_type', get_class($this))
            ->where('subject_id', $this->getKey())
            ->with('causer')
            ->orderBy('created_at', 'desc');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Obtener el último log de actividad
     * 
     * @return \Spatie\Activitylog\Models\Activity|null
     */
    public function getLastActivity()
    {
        return \Spatie\Activitylog\Models\Activity::where('subject_type', get_class($this))
            ->where('subject_id', $this->getKey())
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Contar logs de actividad del modelo
     * 
     * @param string|null $event Filtrar por evento específico
     * @return int
     */
    public function getActivityCount(?string $event = null): int
    {
        $query = \Spatie\Activitylog\Models\Activity::where('subject_type', get_class($this))
            ->where('subject_id', $this->getKey());
        
        if ($event) {
            $query->where('event', $event);
        }
        
        return $query->count();
    }
}