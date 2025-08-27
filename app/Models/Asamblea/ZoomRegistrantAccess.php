<?php

namespace App\Models\Asamblea;

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
        'accessed_at',
        'user_agent',
        'ip_address',
        'masked_url',
        'referer',
        'device_type',
        'browser_name',
    ];

    /**
     * Casts para campos
     */
    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    /**
     * Relación con ZoomRegistrant
     */
    public function zoomRegistrant(): BelongsTo
    {
        return $this->belongsTo(ZoomRegistrant::class);
    }

    /**
     * Crear nuevo registro de acceso
     */
    public static function createAccess(
        int $zoomRegistrantId, 
        ?string $userAgent = null, 
        ?string $ipAddress = null,
        ?string $maskedUrl = null,
        ?string $referer = null
    ): self {
        return self::create([
            'zoom_registrant_id' => $zoomRegistrantId,
            'accessed_at' => now(),
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'masked_url' => $maskedUrl,
            'referer' => $referer,
            'device_type' => self::detectDeviceType($userAgent),
            'browser_name' => self::detectBrowser($userAgent),
        ]);
    }

    /**
     * Detectar tipo de dispositivo desde user agent
     */
    private static function detectDeviceType(?string $userAgent): ?string
    {
        if (!$userAgent) return null;
        
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            return 'tablet';
        }
        
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            return 'mobile';
        }
        
        if (preg_match('/(mobile|phone|ipod)/i', $userAgent)) {
            return 'mobile';
        }
        
        return 'desktop';
    }
    
    /**
     * Detectar navegador desde user agent
     */
    private static function detectBrowser(?string $userAgent): ?string
    {
        if (!$userAgent) return null;
        
        $userAgent = strtolower($userAgent);
        
        if (strpos($userAgent, 'edg') !== false || strpos($userAgent, 'edge') !== false) {
            return 'edge';
        }
        
        if (strpos($userAgent, 'opr') !== false || strpos($userAgent, 'opera') !== false) {
            return 'opera';
        }
        
        if (strpos($userAgent, 'chrome') !== false) {
            return 'chrome';
        }
        
        if (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) {
            return 'safari';
        }
        
        if (strpos($userAgent, 'firefox') !== false) {
            return 'firefox';
        }
        
        if (strpos($userAgent, 'msie') !== false || strpos($userAgent, 'trident') !== false) {
            return 'ie';
        }
        
        return 'other';
    }

    /**
     * Obtener estadísticas de acceso para un zoom registrant
     */
    public static function getStatsForRegistrant(int $zoomRegistrantId): array
    {
        $baseQuery = self::where('zoom_registrant_id', $zoomRegistrantId);
        
        $totalCount = $baseQuery->count();
        
        if ($totalCount === 0) {
            return [
                'total_accesses' => 0,
                'unique_ips' => 0,
                'unique_devices' => 0,
                'first_accessed_at' => null,
                'last_accessed_at' => null,
                'devices' => [],
                'browsers' => [],
                'recent_accesses' => [],
            ];
        }

        // Consultas separadas para evitar conflictos con GROUP BY
        $stats = [
            'total_accesses' => $totalCount,
            'unique_ips' => self::where('zoom_registrant_id', $zoomRegistrantId)->distinct('ip_address')->count('ip_address'),
            'unique_devices' => self::where('zoom_registrant_id', $zoomRegistrantId)->distinct('device_type')->count('device_type'),
            'first_accessed_at' => self::where('zoom_registrant_id', $zoomRegistrantId)->min('accessed_at'),
            'last_accessed_at' => self::where('zoom_registrant_id', $zoomRegistrantId)->max('accessed_at'),
            'devices' => self::where('zoom_registrant_id', $zoomRegistrantId)
                ->selectRaw('device_type, COUNT(*) as count')
                ->groupBy('device_type')
                ->pluck('count', 'device_type')
                ->toArray(),
            'browsers' => self::where('zoom_registrant_id', $zoomRegistrantId)
                ->selectRaw('browser_name, COUNT(*) as count')
                ->groupBy('browser_name')
                ->pluck('count', 'browser_name')
                ->toArray(),
            'recent_accesses' => self::where('zoom_registrant_id', $zoomRegistrantId)
                ->latest('accessed_at')
                ->limit(5)
                ->get(['accessed_at', 'ip_address', 'device_type', 'browser_name'])
                ->toArray(),
        ];
        
        return $stats;
    }

    /**
     * Scope para obtener accesos por zoom_registrant_id con conteo
     */
    public function scopeWithAccessCount($query)
    {
        return $query->selectRaw('zoom_registrant_id, COUNT(*) as access_count')
            ->groupBy('zoom_registrant_id');
    }
    
    /**
     * Scope para registros con más de X accesos (basado en conteo)
     */
    public function scopeHavingManyAccesses($query, int $threshold = 5)
    {
        return $query->selectRaw('zoom_registrant_id, COUNT(*) as access_count')
            ->groupBy('zoom_registrant_id')
            ->havingRaw('COUNT(*) >= ?', [$threshold]);
    }

    /**
     * Scope para registros accedidos recientemente
     */
    public function scopeRecentlyAccessed($query, int $hours = 24)
    {
        return $query->where('accessed_at', '>=', now()->subHours($hours));
    }
    
    /**
     * Scope para accesos desde un dispositivo específico
     */
    public function scopeFromDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }
    
    /**
     * Scope para accesos desde un navegador específico
     */
    public function scopeFromBrowser($query, string $browserName)
    {
        return $query->where('browser_name', $browserName);
    }
}
