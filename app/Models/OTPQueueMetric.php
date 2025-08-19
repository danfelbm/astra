<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OTPQueueMetric extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'otp_queue_metrics';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<string>
     */
    protected $fillable = [
        'channel',
        'status',
        'identifier',
        'user_id',
        'queued_at',
        'processing_at',
        'processed_at',
        'attempts',
        'retry_after',
        'error_message',
        'error_code',
        'metadata',
        'processing_time_ms',
        'throttle_delay_seconds',
        'job_id',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'queued_at' => 'datetime',
        'processing_at' => 'datetime',
        'processed_at' => 'datetime',
        'attempts' => 'integer',
        'retry_after' => 'integer',
        'processing_time_ms' => 'integer',
        'throttle_delay_seconds' => 'integer',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para filtrar por canal
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para obtener métricas recientes
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope para obtener métricas fallidas
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope para obtener métricas throttled
     */
    public function scopeThrottled($query)
    {
        return $query->where('status', 'throttled');
    }

    /**
     * Registrar nueva métrica en cola
     */
    public static function recordQueued(string $channel, string $identifier, ?int $userId = null, array $metadata = []): self
    {
        return self::create([
            'channel' => $channel,
            'status' => 'queued',
            'identifier' => $identifier,
            'user_id' => $userId,
            'queued_at' => now(),
            'metadata' => $metadata,
            'attempts' => 0,
        ]);
    }

    /**
     * Actualizar métrica a procesando
     */
    public function markAsProcessing(): self
    {
        $this->update([
            'status' => 'processing',
            'processing_at' => now(),
            'attempts' => $this->attempts + 1,
        ]);

        return $this;
    }

    /**
     * Actualizar métrica a enviado
     */
    public function markAsSent(): self
    {
        $processingTime = null;
        if ($this->processing_at) {
            $processingTime = now()->diffInMilliseconds($this->processing_at);
        }

        $this->update([
            'status' => 'sent',
            'processed_at' => now(),
            'processing_time_ms' => $processingTime,
        ]);

        return $this;
    }

    /**
     * Actualizar métrica a fallido
     */
    public function markAsFailed(string $errorMessage, ?string $errorCode = null): self
    {
        $this->update([
            'status' => 'failed',
            'processed_at' => now(),
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
        ]);

        return $this;
    }

    /**
     * Actualizar métrica a throttled
     */
    public function markAsThrottled(int $retryAfter): self
    {
        $this->update([
            'status' => 'throttled',
            'retry_after' => $retryAfter,
            'throttle_delay_seconds' => $retryAfter,
        ]);

        return $this;
    }

    /**
     * Obtener estadísticas agregadas por hora
     */
    public static function getHourlyStats(string $channel, int $hours = 24): array
    {
        $stats = [];
        
        for ($i = 0; $i < $hours; $i++) {
            $hourStart = now()->subHours($i)->startOfHour();
            $hourEnd = now()->subHours($i)->endOfHour();
            
            $sent = self::where('channel', $channel)
                ->where('status', 'sent')
                ->whereBetween('processed_at', [$hourStart, $hourEnd])
                ->count();
            
            $failed = self::where('channel', $channel)
                ->where('status', 'failed')
                ->whereBetween('processed_at', [$hourStart, $hourEnd])
                ->count();
            
            $throttled = self::where('channel', $channel)
                ->where('status', 'throttled')
                ->whereBetween('created_at', [$hourStart, $hourEnd])
                ->count();
            
            $avgProcessingTime = self::where('channel', $channel)
                ->where('status', 'sent')
                ->whereBetween('processed_at', [$hourStart, $hourEnd])
                ->avg('processing_time_ms');
            
            $stats[] = [
                'hour' => $hourStart->format('Y-m-d H:00'),
                'sent' => $sent,
                'failed' => $failed,
                'throttled' => $throttled,
                'avg_processing_time_ms' => round($avgProcessingTime ?? 0),
            ];
        }
        
        return array_reverse($stats);
    }

    /**
     * Obtener tiempo promedio de procesamiento
     */
    public static function getAverageProcessingTime(string $channel, int $hours = 1): float
    {
        return self::where('channel', $channel)
            ->where('status', 'sent')
            ->where('processed_at', '>=', now()->subHours($hours))
            ->avg('processing_time_ms') ?? 0;
    }

    /**
     * Obtener tasa de éxito
     */
    public static function getSuccessRate(string $channel, int $hours = 1): float
    {
        $total = self::where('channel', $channel)
            ->whereIn('status', ['sent', 'failed'])
            ->where('processed_at', '>=', now()->subHours($hours))
            ->count();
        
        if ($total === 0) {
            return 0;
        }
        
        $sent = self::where('channel', $channel)
            ->where('status', 'sent')
            ->where('processed_at', '>=', now()->subHours($hours))
            ->count();
        
        return round(($sent / $total) * 100, 2);
    }
}