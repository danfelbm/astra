<?php

namespace Modules\Campanas\Models;

use Modules\Core\Traits\HasTenant;
use Modules\Core\Models\User;
use Modules\Core\Models\Segment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Campana extends Model
{
    use HasFactory, HasTenant, LogsActivity;

    /**
     * Constantes de estados
     */
    const ESTADOS = [
        'borrador' => 'Borrador',
        'programada' => 'Programada',
        'enviando' => 'Enviando',
        'completada' => 'Completada',
        'pausada' => 'Pausada',
        'cancelada' => 'Cancelada',
    ];

    /**
     * Constantes de tipos
     */
    const TIPOS = [
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'ambos' => 'Email y WhatsApp',
    ];

    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'nombre',
        'descripcion',
        'tipo', // email, whatsapp, ambos
        'estado', // borrador, programada, enviando, completada, pausada, cancelada
        'segment_id',
        'plantilla_email_id',
        'plantilla_whatsapp_id',
        'fecha_programada',
        'fecha_inicio',
        'fecha_fin',
        'configuracion',
        'created_by',
        'metadata',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_programada' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'configuracion' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Valores por defecto para atributos
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => 'borrador',
        'configuracion' => '{}',
        'metadata' => '{}',
    ];

    /**
     * Configuración de logs de actividad
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'estado', 'tipo'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Campaña {$eventName}");
    }

    /**
     * Obtener el usuario que creó la campaña
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtener el segmento de la campaña
     */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class, 'segment_id');
    }

    /**
     * Obtener la plantilla de email
     */
    public function plantillaEmail(): BelongsTo
    {
        return $this->belongsTo(PlantillaEmail::class, 'plantilla_email_id');
    }

    /**
     * Obtener la plantilla de WhatsApp
     */
    public function plantillaWhatsApp(): BelongsTo
    {
        return $this->belongsTo(PlantillaWhatsApp::class, 'plantilla_whatsapp_id');
    }

    /**
     * Obtener los envíos de la campaña
     */
    public function envios(): HasMany
    {
        return $this->hasMany(CampanaEnvio::class);
    }

    /**
     * Obtener las métricas de la campaña
     */
    public function metrica(): HasOne
    {
        return $this->hasOne(CampanaMetrica::class);
    }

    /**
     * Verificar si la campaña usa email
     *
     * @return bool
     */
    public function usaEmail(): bool
    {
        return in_array($this->tipo, ['email', 'ambos']);
    }

    /**
     * Verificar si la campaña usa WhatsApp
     *
     * @return bool
     */
    public function usaWhatsApp(): bool
    {
        return in_array($this->tipo, ['whatsapp', 'ambos']);
    }

    /**
     * Obtener configuración específica
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->configuracion, $key, $default);
    }

    /**
     * Establecer configuración específica
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setConfig(string $key, $value): void
    {
        $config = $this->configuracion ?? [];
        data_set($config, $key, $value);
        $this->configuracion = $config;
    }

    /**
     * Obtener los destinatarios de la campaña
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDestinatarios()
    {
        if (!$this->segment) {
            return collect();
        }
        
        return $this->segment->evaluate();
    }

    /**
     * Contar destinatarios totales
     *
     * @return int
     */
    public function contarDestinatarios(): int
    {
        if (!$this->segment) {
            $this->load('segment');
            
            if (!$this->segment) {
                return 0;
            }
        }
        
        return $this->segment->getCount();
    }

    /**
     * Contar destinatarios por tipo
     *
     * @return array
     */
    public function contarDestinatariosPorTipo(): array
    {
        $destinatarios = $this->getDestinatarios();
        
        $conEmail = 0;
        $conWhatsApp = 0;
        $conAmbos = 0;
        
        foreach ($destinatarios as $user) {
            $tieneEmail = !empty($user->email);
            $tieneWhatsApp = !empty($user->telefono);
            
            if ($tieneEmail && $tieneWhatsApp) {
                $conAmbos++;
            } elseif ($tieneEmail) {
                $conEmail++;
            } elseif ($tieneWhatsApp) {
                $conWhatsApp++;
            }
        }
        
        return [
            'total' => $destinatarios->count(),
            'con_email' => $conEmail + $conAmbos,
            'con_whatsapp' => $conWhatsApp + $conAmbos,
            'con_ambos' => $conAmbos,
            'solo_email' => $conEmail,
            'solo_whatsapp' => $conWhatsApp,
        ];
    }

    /**
     * Scopes
     */
    public function scopeBorrador($query)
    {
        return $query->where('estado', 'borrador');
    }

    public function scopeProgramada($query)
    {
        return $query->where('estado', 'programada');
    }

    public function scopeEnviando($query)
    {
        return $query->where('estado', 'enviando');
    }

    public function scopeCompletada($query)
    {
        return $query->where('estado', 'completada');
    }

    public function scopePausada($query)
    {
        return $query->where('estado', 'pausada');
    }

    public function scopeCancelada($query)
    {
        return $query->where('estado', 'cancelada');
    }

    /**
     * Obtener progreso de la campaña
     *
     * @return array
     */
    public function getProgreso(): array
    {
        $totalDestinatarios = $this->contarDestinatarios();
        
        if ($totalDestinatarios === 0) {
            return [
                'porcentaje' => 0,
                'enviados' => 0,
                'pendientes' => 0,
                'fallidos' => 0,
                'total' => 0,
            ];
        }
        
        $enviados = $this->envios()->whereIn('estado', ['enviado', 'abierto', 'click'])->count();
        $fallidos = $this->envios()->where('estado', 'fallido')->count();
        $pendientes = $totalDestinatarios - $enviados - $fallidos;
        
        $porcentaje = round(($enviados / $totalDestinatarios) * 100, 2);
        
        return [
            'porcentaje' => $porcentaje,
            'enviados' => $enviados,
            'pendientes' => $pendientes,
            'fallidos' => $fallidos,
            'total' => $totalDestinatarios,
        ];
    }

    /**
     * Calcular tiempo estimado de finalización
     *
     * @return \Carbon\Carbon|null
     */
    public function calcularTiempoEstimado()
    {
        if (!in_array($this->estado, ['enviando', 'programada'])) {
            return null;
        }
        
        $progreso = $this->getProgreso();
        
        if ($progreso['pendientes'] === 0) {
            return now();
        }
        
        // Calcular rate basado en configuración
        $rateEmail = config('campanas.rate_limits.email', 2);
        $rateWhatsApp = config('campanas.rate_limits.whatsapp', 5);
        
        $segundosEstimados = 0;
        
        if ($this->usaEmail()) {
            $emailsPendientes = $this->envios()
                ->where('tipo', 'email')
                ->where('estado', 'pendiente')
                ->count();
            
            $segundosEstimados = max($segundosEstimados, $emailsPendientes / $rateEmail);
        }
        
        if ($this->usaWhatsApp()) {
            $whatsappPendientes = $this->envios()
                ->where('tipo', 'whatsapp')
                ->where('estado', 'pendiente')
                ->count();
            
            // WhatsApp tiene delay adicional configurable
            $minDelay = $this->getConfig('whatsapp.min_delay', 3);
            $segundosWhatsApp = $whatsappPendientes * $minDelay;
            
            $segundosEstimados = max($segundosEstimados, $segundosWhatsApp);
        }
        
        return now()->addSeconds($segundosEstimados);
    }

    /**
     * Verificar si la campaña puede ser enviada
     *
     * @return array
     */
    public function puedeEnviarse(): array
    {
        $errores = [];
        
        // Verificar estado
        if (!in_array($this->estado, ['borrador', 'programada', 'pausada', 'enviando'])) {
            $errores[] = 'La campaña debe estar en estado borrador, programada, pausada o enviando';
        }
        
        // Verificar segmento
        if (!$this->segment_id) {
            $errores[] = 'Debe seleccionar un segmento';
        }
        
        // Verificar plantillas según tipo
        if ($this->usaEmail() && !$this->plantilla_email_id) {
            $errores[] = 'Debe seleccionar una plantilla de email';
        }
        
        if ($this->usaWhatsApp() && !$this->plantilla_whatsapp_id) {
            $errores[] = 'Debe seleccionar una plantilla de WhatsApp';
        }
        
        // Verificar destinatarios
        $destinatarios = $this->contarDestinatarios();
        
        if ($destinatarios === 0) {
            $errores[] = 'No hay destinatarios en el segmento seleccionado';
        }
        
        return [
            'puede_enviarse' => empty($errores),
            'errores' => $errores,
        ];
    }
}