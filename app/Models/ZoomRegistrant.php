<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    ];

    /**
     * Casts para campos
     */
    protected $casts = [
        'zoom_occurrences' => 'array',
        'zoom_start_time' => 'datetime',
        'registered_at' => 'datetime',
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
     * Relación con ZoomRegistrantAccess
     */
    public function access(): HasOne
    {
        return $this->hasOne(ZoomRegistrantAccess::class);
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
}
