<?php

namespace App\Models;

use App\Traits\HasTenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OTP extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'otps';

    protected $fillable = [
        'email',
        'codigo',
        'expira_en',
        'usado',
        'canal_enviado',
        'whatsapp_sent_at',
        'telefono_destino',
    ];

    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
            'usado' => 'boolean',
            'whatsapp_sent_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include non-expired OTPs.
     */
    public function scopeVigentes($query)
    {
        return $query->where('expira_en', '>', Carbon::now())
                    ->where('usado', false);
    }

    /**
     * Check if the OTP is expired.
     */
    public function isExpired()
    {
        return $this->expira_en->isPast();
    }

    /**
     * Check if the OTP is valid (not expired and not used).
     */
    public function isValid()
    {
        return !$this->usado && !$this->isExpired();
    }

    /**
     * Mark the OTP as used.
     */
    public function markAsUsed()
    {
        $this->update(['usado' => true]);
    }
}
