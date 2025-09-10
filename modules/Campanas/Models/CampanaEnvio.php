<?php

namespace Modules\Campanas\Models;

use Modules\Core\Traits\HasTenant;
use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CampanaEnvio extends Model
{
    use HasFactory, HasTenant;

    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'campana_id',
        'user_id',
        'tipo', // email, whatsapp
        'estado', // pendiente, enviando, enviado, abierto, click, fallido
        'tracking_id',
        'destinatario', // email o teléfono
        'fecha_enviado',
        'fecha_abierto',
        'fecha_primer_click',
        'fecha_ultimo_click',
        'clicks_count',
        'aperturas_count',
        'error_mensaje',
        'metadata',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_enviado' => 'datetime',
        'fecha_abierto' => 'datetime',
        'fecha_primer_click' => 'datetime',
        'fecha_ultimo_click' => 'datetime',
        'clicks_count' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Valores por defecto para atributos
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'pendiente',
        'clicks_count' => 0,
        'aperturas_count' => 0,
        'metadata' => '{}',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($envio) {
            // Generar tracking_id único si no existe
            if (!$envio->tracking_id) {
                $envio->tracking_id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Obtener la campaña
     */
    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class);
    }

    /**
     * Obtener el usuario destinatario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marcar como enviado
     *
     * @return void
     */
    public function marcarEnviado(): void
    {
        $this->update([
            'estado' => 'enviado',
            'fecha_enviado' => now(),
        ]);
    }

    /**
     * Marcar como abierto (solo para emails) - DEPRECADO, usar registrarApertura()
     *
     * @return void
     * @deprecated Use registrarApertura() instead
     */
    public function marcarAbierto(): void
    {
        $this->registrarApertura();
    }
    
    /**
     * Registrar una apertura del email (registra todas las aperturas)
     *
     * @return void
     */
    public function registrarApertura(): void
    {
        if ($this->tipo !== 'email') {
            return;
        }
        
        $ahora = now();
        $updates = [
            'aperturas_count' => $this->aperturas_count + 1,
        ];
        
        // Si es la primera apertura
        if (!$this->fecha_abierto) {
            $updates['fecha_abierto'] = $ahora;
            $updates['estado'] = 'abierto';
        }
        
        // Si ya hay clics, mantener estado como 'click'
        if ($this->estado === 'click') {
            unset($updates['estado']);
        }
        
        $this->update($updates);
    }

    /**
     * Registrar un click (solo para emails)
     *
     * @param string|null $url
     * @return void
     */
    public function registrarClick(string $url = null): void
    {
        if ($this->tipo !== 'email') {
            return;
        }
        
        $ahora = now();
        $updates = [
            'estado' => 'click',
            'clicks_count' => $this->clicks_count + 1,
            'fecha_ultimo_click' => $ahora,
        ];
        
        // Si es el primer click
        if (!$this->fecha_primer_click) {
            $updates['fecha_primer_click'] = $ahora;
        }
        
        // Si nunca se marcó como abierto, marcarlo ahora
        if (!$this->fecha_abierto) {
            $updates['fecha_abierto'] = $ahora;
        }
        
        $this->update($updates);
        
        // NO actualizamos metadata aquí, se hace en CampanaTrackingService
        // para evitar condiciones de carrera
    }

    /**
     * Marcar como fallido
     *
     * @param string $mensaje
     * @return void
     */
    public function marcarFallido(string $mensaje): void
    {
        $this->update([
            'estado' => 'fallido',
            'error_mensaje' => $mensaje,
        ]);
    }

    /**
     * Obtener URL del pixel de tracking
     *
     * @return string
     */
    public function getPixelUrl(): string
    {
        return route('campanas.tracking.pixel', [
            'trackingId' => $this->tracking_id,
        ]);
    }

    /**
     * Obtener URL de redirección para tracking de clicks
     *
     * @param string $originalUrl
     * @return string
     */
    public function getClickUrl(string $originalUrl): string
    {
        return route('campanas.tracking.click', [
            'trackingId' => $this->tracking_id,  // Cambiar a trackingId (camelCase)
            'url' => base64_encode($originalUrl),
        ]);
    }

    /**
     * Procesar contenido HTML para agregar tracking
     *
     * @param string $html
     * @return string
     */
    public function procesarHtmlConTracking(string $html): string
    {
        // Solo para emails
        if ($this->tipo !== 'email') {
            return $html;
        }
        
        // Agregar pixel de tracking si está habilitado
        if (config('campanas.tracking.pixel_enabled', true)) {
            $pixelHtml = sprintf(
                '<img src="%s" width="1" height="1" style="display:none;" alt="" />',
                $this->getPixelUrl()
            );
            
            // Insertar antes del cierre del body si existe, o al final
            if (stripos($html, '</body>') !== false) {
                $html = str_ireplace('</body>', $pixelHtml . '</body>', $html);
            } else {
                $html .= $pixelHtml;
            }
        }
        
        // Reemplazar URLs para tracking de clicks si está habilitado
        if (config('campanas.tracking.click_tracking', true)) {
            $html = $this->reemplazarUrlsParaTracking($html);
        }
        
        return $html;
    }

    /**
     * Reemplazar URLs en el HTML para tracking
     *
     * @param string $html
     * @return string
     */
    protected function reemplazarUrlsParaTracking(string $html): string
    {
        // Patrón para encontrar URLs en href
        $pattern = '/href=["\']([^"\']+)["\']/i';
        
        return preg_replace_callback($pattern, function ($matches) {
            $url = $matches[1];
            
            // No trackear anchors internos, mailto, tel, etc.
            if (strpos($url, '#') === 0 || 
                strpos($url, 'mailto:') === 0 || 
                strpos($url, 'tel:') === 0 ||
                strpos($url, 'javascript:') === 0) {
                return $matches[0];
            }
            
            // Reemplazar con URL de tracking
            $trackingUrl = $this->getClickUrl($url);
            
            return 'href="' . $trackingUrl . '"';
        }, $html);
    }

    /**
     * Scopes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeEnviados($query)
    {
        return $query->whereIn('estado', ['enviado', 'abierto', 'click']);
    }

    public function scopeFallidos($query)
    {
        return $query->where('estado', 'fallido');
    }

    public function scopeAbiertos($query)
    {
        return $query->whereIn('estado', ['abierto', 'click']);
    }

    public function scopeConClicks($query)
    {
        return $query->where('estado', 'click');
    }

    public function scopeTipoEmail($query)
    {
        return $query->where('tipo', 'email');
    }

    public function scopeTipoWhatsApp($query)
    {
        return $query->where('tipo', 'whatsapp');
    }

    /**
     * Calcular tiempo desde el envío
     *
     * @return string|null
     */
    public function getTiempoDesdeEnvio(): ?string
    {
        if (!$this->fecha_enviado) {
            return null;
        }
        
        return $this->fecha_enviado->diffForHumans();
    }

    /**
     * Calcular tiempo hasta la apertura
     *
     * @return int|null Minutos
     */
    public function getTiempoHastaApertura(): ?int
    {
        if (!$this->fecha_enviado || !$this->fecha_abierto) {
            return null;
        }
        
        return (int) round($this->fecha_enviado->diffInMinutes($this->fecha_abierto));
    }

    /**
     * Obtener información de dispositivo desde metadata
     *
     * @return array|null
     */
    public function getDispositivoInfo(): ?array
    {
        return $this->metadata['device'] ?? null;
    }
}