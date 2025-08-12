<?php

namespace App\Models;

use App\Traits\HasTenant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class PostulacionHistorial extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'postulacion_historiales';

    protected $fillable = [
        'postulacion_id',
        'estado_anterior',
        'estado_nuevo',
        'comentarios',
        'motivo_cambio',
        'cambiado_por',
        'fecha_cambio',
        'metadatos',
    ];

    protected $casts = [
        'fecha_cambio' => 'datetime',
        'metadatos' => 'array',
    ];

    // Relaciones
    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class);
    }

    public function cambiadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cambiado_por');
    }

    // Scopes
    public function scopeDePostulacion(Builder $query, int $postulacionId): Builder
    {
        return $query->where('postulacion_id', $postulacionId);
    }

    public function scopeOrdenadoPorFecha(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('fecha_cambio', $direction);
    }

    public function scopeRecientes(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('fecha_cambio', 'desc')->limit($limit);
    }

    // Método estático para crear registro de historial
    public static function crearRegistro(
        Postulacion $postulacion,
        string $estadoAnterior,
        string $estadoNuevo,
        User $admin,
        ?string $comentarios = null,
        ?string $motivoCambio = null,
        array $metadatos = []
    ): self {
        return self::create([
            'postulacion_id' => $postulacion->id,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'comentarios' => $comentarios,
            'motivo_cambio' => $motivoCambio,
            'cambiado_por' => $admin->id,
            'fecha_cambio' => Carbon::now(),
            'metadatos' => array_merge([
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'session_id' => session()?->getId(),
            ], $metadatos),
        ]);
    }

    // Getters útiles
    public function getEstadoAnteriorLabelAttribute(): string
    {
        return $this->getEstadoLabel($this->estado_anterior);
    }

    public function getEstadoNuevoLabelAttribute(): string
    {
        return $this->getEstadoLabel($this->estado_nuevo);
    }

    public function getEstadoAnteriorColorAttribute(): string
    {
        return $this->getEstadoColor($this->estado_anterior);
    }

    public function getEstadoNuevoColorAttribute(): string
    {
        return $this->getEstadoColor($this->estado_nuevo);
    }

    public function getFechaFormateadaAttribute(): string
    {
        return $this->fecha_cambio->format('d/m/Y H:i');
    }

    public function getResumenCambioAttribute(): string
    {
        return $this->estado_anterior_label . ' → ' . $this->estado_nuevo_label;
    }

    public function getTiempoPasadoAttribute(): string
    {
        return $this->fecha_cambio->diffForHumans();
    }

    // Métodos helper privados
    private function getEstadoLabel(string $estado): string
    {
        return match($estado) {
            'borrador' => 'Borrador',
            'enviada' => 'Enviada',
            'en_revision' => 'En Revisión',
            'aceptada' => 'Aceptada',
            'rechazada' => 'Rechazada',
            default => 'Desconocido',
        };
    }

    private function getEstadoColor(string $estado): string
    {
        return match($estado) {
            'borrador' => 'bg-yellow-100 text-yellow-800',
            'enviada' => 'bg-blue-100 text-blue-800',
            'en_revision' => 'bg-purple-100 text-purple-800',
            'aceptada' => 'bg-green-100 text-green-800',
            'rechazada' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    // Método para obtener icono del cambio
    public function getTipoCambioIconAttribute(): string
    {
        if ($this->estado_nuevo === 'aceptada') {
            return 'CheckCircle';
        } elseif ($this->estado_nuevo === 'rechazada') {
            return 'XCircle';
        } elseif ($this->estado_nuevo === 'en_revision') {
            return 'Clock';
        } elseif ($this->estado_nuevo === 'enviada') {
            return 'Send';
        } elseif ($this->estado_nuevo === 'borrador') {
            return 'Edit';
        }
        
        return 'FileText';
    }

    // Método para determinar si el cambio fue positivo, negativo o neutro
    public function getTipoCambioAttribute(): string
    {
        if ($this->estado_nuevo === 'aceptada') {
            return 'positivo';
        } elseif ($this->estado_nuevo === 'rechazada') {
            return 'negativo';
        } elseif ($this->estado_nuevo === 'en_revision') {
            return 'progreso';
        } elseif ($this->estado_nuevo === 'enviada') {
            return 'progreso';
        }
        
        return 'neutro';
    }
}
