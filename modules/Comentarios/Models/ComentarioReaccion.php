<?php

namespace Modules\Comentarios\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasTenant;

class ComentarioReaccion extends Model
{
    use HasTenant;

    protected $table = 'comentario_reacciones';

    // Solo tiene created_at
    public $timestamps = false;

    protected $fillable = [
        'comentario_id',
        'user_id',
        'emoji',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * Comentario al que pertenece la reacción.
     */
    public function comentario(): BelongsTo
    {
        return $this->belongsTo(Comentario::class, 'comentario_id');
    }

    /**
     * Usuario que hizo la reacción.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Obtiene el símbolo del emoji.
     */
    public function getSimboloAttribute(): string
    {
        return Comentario::EMOJIS[$this->emoji] ?? $this->emoji;
    }

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        // Asignar created_at al crear
        static::creating(function (ComentarioReaccion $reaccion) {
            if (empty($reaccion->created_at)) {
                $reaccion->created_at = now();
            }

            // Asignar user_id si no está definido
            if (empty($reaccion->user_id) && auth()->check()) {
                $reaccion->user_id = auth()->id();
            }
        });
    }
}
