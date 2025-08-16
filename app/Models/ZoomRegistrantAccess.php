<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoomRegistrantAccess extends Model
{
    use HasFactory;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'zoom_registrant_id',
        'access_count',
        'first_accessed_at',
        'last_accessed_at',
        'user_agent',
        'ip_address',
        'masked_url',
    ];

    /**
     * Casts para campos
     */
    protected $casts = [
        'access_count' => 'integer',
        'first_accessed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Relación con ZoomRegistrant
     */
    public function zoomRegistrant(): BelongsTo
    {
        return $this->belongsTo(ZoomRegistrant::class);
    }

    /**
     * Incrementar contador de acceso con información adicional
     */
    public function incrementAccess(?string $userAgent = null, ?string $ipAddress = null, ?string $maskedUrl = null): void
    {
        $now = now();
        
        $this->update([
            'access_count' => $this->access_count + 1,
            'first_accessed_at' => $this->first_accessed_at ?? $now,
            'last_accessed_at' => $now,
            'user_agent' => $userAgent ?? $this->user_agent,
            'ip_address' => $ipAddress ?? $this->ip_address,
            'masked_url' => $maskedUrl ?? $this->masked_url,
        ]);
    }

    /**
     * Crear o actualizar registro de acceso
     */
    public static function createOrUpdateAccess(
        int $zoomRegistrantId, 
        ?string $userAgent = null, 
        ?string $ipAddress = null,
        ?string $maskedUrl = null
    ): self {
        $access = self::firstOrCreate([
            'zoom_registrant_id' => $zoomRegistrantId,
        ], [
            'access_count' => 0,
        ]);

        $access->incrementAccess($userAgent, $ipAddress, $maskedUrl);
        
        return $access->fresh();
    }

    /**
     * Obtener estadísticas de acceso para un zoom registrant
     */
    public static function getStatsForRegistrant(int $zoomRegistrantId): array
    {
        $access = self::where('zoom_registrant_id', $zoomRegistrantId)->first();
        
        if (!$access) {
            return [
                'total_accesses' => 0,
                'first_accessed_at' => null,
                'last_accessed_at' => null,
                'user_agent' => null,
                'ip_address' => null,
                'masked_url' => null,
            ];
        }

        return [
            'total_accesses' => $access->access_count,
            'first_accessed_at' => $access->first_accessed_at,
            'last_accessed_at' => $access->last_accessed_at,
            'user_agent' => $access->user_agent,
            'ip_address' => $access->ip_address,
            'masked_url' => $access->masked_url,
        ];
    }

    /**
     * Scope para registros con más de X accesos
     */
    public function scopeWithManyAccesses($query, int $threshold = 5)
    {
        return $query->where('access_count', '>=', $threshold);
    }

    /**
     * Scope para registros accedidos recientemente
     */
    public function scopeRecentlyAccessed($query, int $hours = 24)
    {
        return $query->where('last_accessed_at', '>=', now()->subHours($hours));
    }
}
