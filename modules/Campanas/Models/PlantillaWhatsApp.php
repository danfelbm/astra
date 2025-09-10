<?php

namespace Modules\Campanas\Models;

use Modules\Core\Traits\HasTenant;
use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PlantillaWhatsApp extends Model
{
    use HasFactory, HasTenant, LogsActivity;

    /**
     * Los atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'nombre',
        'descripcion',
        'contenido',
        'variables_usadas',
        'usa_formato',
        'es_activa',
        'created_by',
        'metadata',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'variables_usadas' => 'array',
        'usa_formato' => 'boolean',
        'es_activa' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Valores por defecto para atributos
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'es_activa' => true,
        'usa_formato' => true, // Por defecto usa formato con emojis y negritas
        'variables_usadas' => '[]',
        'metadata' => '{}',
    ];

    /**
     * Configuración de logs de actividad
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'es_activa'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Plantilla de WhatsApp {$eventName}");
    }

    /**
     * Obtener el usuario que creó la plantilla
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtener las campañas que usan esta plantilla
     */
    public function campanas(): HasMany
    {
        return $this->hasMany(Campana::class, 'plantilla_whatsapp_id');
    }

    /**
     * Procesar el contenido con las variables del usuario
     *
     * @param User $user
     * @return string
     */
    public function procesarContenido(User $user): string
    {
        $contenido = $this->contenido;
        
        // Reemplazar variables del usuario
        $variables = $this->obtenerVariables($user);
        
        foreach ($variables as $key => $value) {
            $contenido = str_replace('{{' . $key . '}}', $value ?? '', $contenido);
        }
        
        // Aplicar formato si está habilitado
        if ($this->usa_formato) {
            $contenido = $this->aplicarFormatoWhatsApp($contenido);
        }
        
        return $contenido;
    }

    /**
     * Obtener las variables disponibles para un usuario
     *
     * @param User $user
     * @return array
     */
    protected function obtenerVariables(User $user): array
    {
        $variables = [
            'nombre' => $user->name,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'documento_identidad' => $user->documento_identidad,
            'primer_nombre' => explode(' ', $user->name)[0] ?? $user->name,
        ];
        
        // Agregar variables de ubicación si existen
        if ($user->territorio) {
            $variables['territorio'] = $user->territorio->nombre;
        }
        
        if ($user->departamento) {
            $variables['departamento'] = $user->departamento->nombre;
        }
        
        if ($user->municipio) {
            $variables['municipio'] = $user->municipio->nombre;
        }
        
        if ($user->localidad) {
            $variables['localidad'] = $user->localidad->nombre;
        }
        
        // Agregar fecha actual
        $variables['fecha_actual'] = now()->format('d/m/Y');
        $variables['ano_actual'] = now()->year;
        
        return $variables;
    }

    /**
     * Aplicar formato de WhatsApp (negritas con asteriscos)
     *
     * @param string $contenido
     * @return string
     */
    protected function aplicarFormatoWhatsApp(string $contenido): string
    {
        // Convertir **texto** a *texto* para negritas en WhatsApp
        $contenido = preg_replace('/\*\*([^*]+)\*\*/', '*$1*', $contenido);
        
        // Convertir __texto__ a _texto_ para cursivas en WhatsApp
        $contenido = preg_replace('/__([^_]+)__/', '_$1_', $contenido);
        
        // Convertir ~~texto~~ a ~texto~ para tachado en WhatsApp
        $contenido = preg_replace('/~~([^~]+)~~/', '~$1~', $contenido);
        
        return $contenido;
    }

    /**
     * Extraer variables usadas del contenido
     *
     * @return array
     */
    public function extraerVariables(): array
    {
        $patron = '/\{\{([^}]+)\}\}/';
        $variables = [];
        
        // Buscar en el contenido
        preg_match_all($patron, $this->contenido, $matches);
        $variables = $matches[1] ?? [];
        
        // Eliminar duplicados y retornar
        return array_unique($variables);
    }

    /**
     * Actualizar las variables usadas antes de guardar
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($plantilla) {
            $plantilla->variables_usadas = $plantilla->extraerVariables();
        });
    }

    /**
     * Scope para plantillas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('es_activa', true);
    }

    /**
     * Obtener preview del mensaje
     *
     * @param int $length
     * @return string
     */
    public function getPreview(int $length = 100): string
    {
        $contenido = strip_tags($this->contenido);
        $contenido = str_replace(['*', '_', '~'], '', $contenido);
        
        if (strlen($contenido) <= $length) {
            return $contenido;
        }
        
        return substr($contenido, 0, $length) . '...';
    }

    /**
     * Obtener estadísticas de uso
     *
     * @return array
     */
    public function getEstadisticas(): array
    {
        $campanasCount = $this->campanas()->count();
        $enviosCount = $this->campanas()
            ->withCount('envios')
            ->get()
            ->sum('envios_count');
        
        $ultimoUso = $this->campanas()
            ->latest()
            ->first()?->created_at;
        
        return [
            'campanas_count' => $campanasCount,
            'envios_count' => $enviosCount,
            'ultimo_uso' => $ultimoUso,
        ];
    }

    /**
     * Calcular longitud del mensaje en caracteres SMS
     *
     * @return int
     */
    public function calcularLongitudSMS(): int
    {
        // Limpiar formato y variables para cálculo aproximado
        $contenido = $this->contenido;
        $contenido = preg_replace('/\{\{[^}]+\}\}/', 'XXXXXXXX', $contenido); // Reemplazar variables con texto promedio
        $contenido = str_replace(['*', '_', '~'], '', $contenido); // Quitar formato
        
        return strlen($contenido);
    }
}