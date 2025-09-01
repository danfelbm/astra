<?php

namespace App\Models\Votaciones;

use App\Models\Core\User;
use App\Traits\HasTenant;
use App\Traits\HasAuditLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrnaSession extends Model
{
    use HasFactory, HasTenant, HasAuditLog;

    /**
     * Nombre del log para auditoría
     */
    protected $auditLogName = 'urna_sessions';

    protected $fillable = [
        'votacion_id',
        'usuario_id',
        'opened_at',
        'closed_at',
        'status',
        'ip_address',
        'user_agent',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Obtener la votación asociada
     */
    public function votacion()
    {
        return $this->belongsTo(Votacion::class);
    }

    /**
     * Obtener el usuario asociado
     */
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verificar si la sesión está activa
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               Carbon::now()->lessThan($this->expires_at);
    }

    /**
     * Verificar si la sesión ha expirado
     */
    public function hasExpired(): bool
    {
        return Carbon::now()->greaterThanOrEqualTo($this->expires_at);
    }

    /**
     * Obtener tiempo restante en segundos
     */
    public function getRemainingSeconds(): int
    {
        if (!$this->isActive()) {
            return 0;
        }
        
        return max(0, Carbon::now()->diffInSeconds($this->expires_at, false));
    }

    /**
     * Obtener tiempo restante formateado
     */
    public function getRemainingTimeFormatted(): string
    {
        $seconds = $this->getRemainingSeconds();
        
        if ($seconds <= 0) {
            return 'Expirada';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }

    /**
     * Cerrar la sesión por voto exitoso
     */
    public function closeByVote(): void
    {
        $this->update([
            'status' => 'voted',
            'closed_at' => Carbon::now(),
        ]);
        
        // Registrar en audit log
        $this->logAction('cerró urna', 'Voto completado exitosamente');
    }

    /**
     * Expirar la sesión eliminándola completamente
     */
    public function expire(): void
    {
        if ($this->status === 'active') {
            // Registrar en audit log antes de eliminar
            $this->logAction('expiró urna', 'Sesión eliminada por tiempo agotado');
            
            // Eliminar la sesión completamente para evitar problemas de constraint único
            // Esto permite al usuario reintentar inmediatamente
            $this->delete();
        }
    }

    /**
     * Scope para sesiones activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope para sesiones expiradas que necesitan limpieza
     */
    public function scopeExpiredForCleanup($query)
    {
        return $query->where('status', 'active')
                     ->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Obtener descripción del evento para audit log
     */
    protected function getEventDescription(string $eventName): string
    {
        $userName = $this->usuario ? $this->usuario->name : 'Usuario';
        $votacionTitulo = $this->votacion ? $this->votacion->titulo : 'Votación';
        
        return match($eventName) {
            'created' => "{$userName} abrió urna para {$votacionTitulo}",
            'updated' => "Sesión de urna actualizada para {$userName} en {$votacionTitulo}",
            'deleted' => "Sesión de urna eliminada para {$userName} en {$votacionTitulo}",
            default => "{$userName} realizó {$eventName} en sesión de urna para {$votacionTitulo}"
        };
    }
}