<?php

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsvImport extends Model
{
    use HasTenant;
    protected $fillable = [
        'votacion_id',
        'filename',
        'original_filename',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'errors',
        'batch_size',
        'motivo',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
        'batch_size' => 'integer',
    ];

    // Relaciones
    public function votacion(): BelongsTo
    {
        return $this->belongsTo(Votacion::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    public function scopeForVotacion(Builder $query, int $votacionId): Builder
    {
        return $query->where('votacion_id', $votacionId);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Accessors
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }
        
        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === 'failed';
    }

    public function getHasErrorsAttribute(): bool
    {
        return !empty($this->errors) || $this->failed_rows > 0;
    }

    public function getErrorCountAttribute(): int
    {
        return is_array($this->errors) ? count($this->errors) : 0;
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();
        $duration = $this->started_at->diffInSeconds($end);
        
        if ($duration < 60) {
            return "{$duration}s";
        } elseif ($duration < 3600) {
            return round($duration / 60, 1) . "m";
        } else {
            return round($duration / 3600, 1) . "h";
        }
    }

    // Métodos de conveniencia
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(array $errors = []): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'errors' => array_merge($this->errors ?? [], $errors),
        ]);
    }

    public function updateProgress(int $processed, int $successful, int $failed, array $newErrors = []): void
    {
        $this->update([
            'processed_rows' => $processed,
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'errors' => array_merge($this->errors ?? [], $newErrors),
        ]);
    }
}
