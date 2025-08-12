<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voto extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'votacion_id',
        'usuario_id',
        'token_unico',
        'respuestas',
        'ip_address',
        'user_agent',
    ];

    protected $hidden = [
        'usuario_id', // Para anonimización
    ];

    protected function casts(): array
    {
        return [
            'respuestas' => 'array',
        ];
    }

    /**
     * Get the votacion that owns the voto.
     */
    public function votacion()
    {
        return $this->belongsTo(Votacion::class);
    }

    /**
     * Get the usuario that owns the voto.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
