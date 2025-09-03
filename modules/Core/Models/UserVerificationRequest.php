<?php

namespace Modules\Core\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVerificationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'documento_identidad',
        'user_id',
        'session_token',
        'verification_code_email',
        'verification_code_whatsapp',
        'email_sent_at',
        'whatsapp_sent_at',
        'email_verified_at',
        'whatsapp_verified_at',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'email_sent_at' => 'datetime',
            'whatsapp_sent_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'whatsapp_verified_at' => 'datetime',
        ];
    }

    /**
     * Relación con el usuario encontrado
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verifica si el email ha sido verificado
     */
    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Verifica si WhatsApp ha sido verificado
     */
    public function isWhatsappVerified(): bool
    {
        return $this->whatsapp_verified_at !== null;
    }

    /**
     * Verifica si ambos canales han sido verificados
     */
    public function isFullyVerified(): bool
    {
        // Si se enviaron ambos códigos, ambos deben estar verificados
        if ($this->verification_code_email && $this->verification_code_whatsapp) {
            return $this->isEmailVerified() && $this->isWhatsappVerified();
        }
        
        // Si solo se envió uno, ese debe estar verificado
        if ($this->verification_code_email) {
            return $this->isEmailVerified();
        }
        
        if ($this->verification_code_whatsapp) {
            return $this->isWhatsappVerified();
        }
        
        return false;
    }

    /**
     * Verifica si los códigos han expirado (15 minutos)
     */
    public function hasExpired(): bool
    {
        $expirationTime = 15; // minutos
        
        // Verificar expiración del código de email
        if ($this->email_sent_at && !$this->email_verified_at) {
            if ($this->email_sent_at->addMinutes($expirationTime)->isPast()) {
                return true;
            }
        }
        
        // Verificar expiración del código de WhatsApp
        if ($this->whatsapp_sent_at && !$this->whatsapp_verified_at) {
            if ($this->whatsapp_sent_at->addMinutes($expirationTime)->isPast()) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Marca el código de email como verificado
     */
    public function markEmailAsVerified(): void
    {
        $this->update([
            'email_verified_at' => now(),
        ]);
        
        $this->checkAndUpdateStatus();
    }

    /**
     * Marca el código de WhatsApp como verificado
     */
    public function markWhatsappAsVerified(): void
    {
        $this->update([
            'whatsapp_verified_at' => now(),
        ]);
        
        $this->checkAndUpdateStatus();
    }

    /**
     * Actualiza el estado basado en las verificaciones
     */
    protected function checkAndUpdateStatus(): void
    {
        if ($this->isFullyVerified()) {
            $this->update(['status' => 'verified']);
        }
    }

    /**
     * Genera un código único de 6 dígitos
     */
    public static function generateUniqueCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Scope para solicitudes pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para solicitudes verificadas
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope para solicitudes recientes (últimas 24 horas)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDay());
    }

    /**
     * Verifica si se puede reenviar códigos (límite de 3 intentos en 1 hora)
     */
    public function canResendCodes(): bool
    {
        $attemptsInLastHour = self::where('documento_identidad', $this->documento_identidad)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();
            
        return $attemptsInLastHour < 3;
    }

    /**
     * Obtiene el tiempo restante para la expiración en minutos
     */
    public function getMinutesUntilExpiration(): int
    {
        $expirationTime = 15; // minutos
        
        if ($this->email_sent_at && !$this->email_verified_at) {
            $minutesLeft = $this->email_sent_at->addMinutes($expirationTime)->diffInMinutes(now(), false);
            if ($minutesLeft < 0) {
                return abs($minutesLeft);
            }
        }
        
        if ($this->whatsapp_sent_at && !$this->whatsapp_verified_at) {
            $minutesLeft = $this->whatsapp_sent_at->addMinutes($expirationTime)->diffInMinutes(now(), false);
            if ($minutesLeft < 0) {
                return abs($minutesLeft);
            }
        }
        
        return 0;
    }
}