<?php

namespace Modules\Comentarios\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasTenant;

class ComentarioMencion extends Model
{
    use HasTenant;

    protected $table = 'comentario_menciones';

    // Solo tiene created_at
    public $timestamps = false;

    protected $fillable = [
        'comentario_id',
        'user_id',
        'notificado',
        'created_at',
    ];

    protected $casts = [
        'notificado' => 'boolean',
        'created_at' => 'datetime',
    ];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * Comentario que contiene la mención.
     */
    public function comentario(): BelongsTo
    {
        return $this->belongsTo(Comentario::class, 'comentario_id');
    }

    /**
     * Usuario mencionado.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Menciones pendientes de notificar.
     */
    public function scopePendientes($query)
    {
        return $query->where('notificado', false);
    }

    /**
     * Menciones de un usuario específico.
     */
    public function scopeParaUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // =========================================================================
    // MÉTODOS
    // =========================================================================

    /**
     * Marca la mención como notificada.
     * TODO: Implementar sistema de notificaciones en desarrollo posterior.
     */
    public function marcarComoNotificado(): bool
    {
        return $this->update(['notificado' => true]);
    }

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        // Asignar created_at al crear
        static::creating(function (ComentarioMencion $mencion) {
            if (empty($mencion->created_at)) {
                $mencion->created_at = now();
            }
        });
    }
}
