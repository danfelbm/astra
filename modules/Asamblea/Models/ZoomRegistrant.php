<?php

namespace Modules\Asamblea\Models;

use Modules\Core\Models\User;
use Modules\Core\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZoomRegistrant extends Model
{
    use HasFactory, HasTenant;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'tenant_id',
        'asamblea_id',
        'user_id',
        'zoom_registrant_id',
        'zoom_join_url',
        'zoom_participant_pin_code',
        'zoom_start_time',
        'zoom_topic',
        'zoom_occurrences',
        'registered_at',
        'status',
        'error_message',
        'processing_started_at',
    ];

    /**
     * Casts para campos
     */
    protected $casts = [
        'zoom_occurrences' => 'array',
        'zoom_start_time' => 'datetime',
        'registered_at' => 'datetime',
        'processing_started_at' => 'datetime',
    ];

    /**
     * Relación con Asamblea
     */
    public function asamblea(): BelongsTo
    {
        return $this->belongsTo(Asamblea::class);
    }

    /**
     * Relación con Usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verificar si el registro aún es válido
     */
    public function isValid(): bool
    {
        if (!$this->zoom_start_time) {
            return true; // Si no hay hora específica, asumimos que es válido
        }

        $now = now();
        $meetingEnd = $this->zoom_start_time->addHours(2); // Asumimos 2 horas de duración máxima

        return $now <= $meetingEnd;
    }

    /**
     * Obtener el estado del registro
     */
    public function getStatus(): string
    {
        if (!$this->isValid()) {
            return 'expired';
        }

        if ($this->zoom_start_time && now() >= $this->zoom_start_time) {
            return 'active';
        }

        return 'pending';
    }

    /**
     * Obtener mensaje del estado
     */
    public function getStatusMessage(): string
    {
        $status = $this->getStatus();

        return match($status) {
            'pending' => 'Reunión aún no iniciada',
            'active' => 'Reunión en curso',
            'expired' => 'Reunión finalizada',
            default => 'Estado desconocido'
        };
    }

    /**
     * Verificar si el registro está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verificar si el registro está en procesamiento
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Verificar si el registro fue completado exitosamente
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Verificar si el registro falló
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Obtener mensaje del estado de registro
     */
    public function getRegistrationStatusMessage(): string
    {
        return match($this->status) {
            'pending' => 'Registro pendiente de procesamiento',
            'processing' => 'Registrando en Zoom...',
            'completed' => 'Registro completado exitosamente',
            'failed' => 'Error en el registro: ' . ($this->error_message ?? 'Error desconocido'),
            default => 'Estado de registro desconocido'
        };
    }

    /**
     * Relación con ZoomRegistrantAccess (ahora hasMany para múltiples accesos)
     */
    public function accesses(): HasMany
    {
        return $this->hasMany(ZoomRegistrantAccess::class);
    }
    
    /**
     * Helper para obtener el conteo de accesos (compatibilidad)
     */
    public function getAccessCountAttribute(): int
    {
        return $this->accesses()->count();
    }
    
    /**
     * Helper para obtener el primer acceso
     */
    public function getFirstAccessAttribute()
    {
        return $this->accesses()->oldest('accessed_at')->first();
    }
    
    /**
     * Helper para obtener el último acceso
     */
    public function getLastAccessAttribute()
    {
        return $this->accesses()->latest('accessed_at')->first();
    }
    
    /**
     * Relación legacy para compatibilidad (deprecada)
     * @deprecated Usar accesses() en su lugar
     */
    public function access()
    {
        return $this->accesses()->latest('accessed_at')->limit(1);
    }

    /**
     * Scope para registros activos
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('zoom_start_time')
              ->orWhere('zoom_start_time', '<=', now()->addHours(2));
        });
    }

    /**
     * Scope para registros de una asamblea específica
     */
    public function scopeForAsamblea($query, int $asambleaId)
    {
        return $query->where('asamblea_id', $asambleaId);
    }

    /**
     * Scope para registros de un usuario específico
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para registros pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para registros en procesamiento
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope para registros completados
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope para registros fallidos
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope para registros atorados (en procesamiento por mucho tiempo)
     */
    public function scopeStuck($query, int $minutesThreshold = 10)
    {
        return $query->where('status', 'processing')
                    ->where('processing_started_at', '<', now()->subMinutes($minutesThreshold));
    }
}
