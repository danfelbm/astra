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

class PlantillaEmail extends Model
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
        'asunto',
        'contenido_html',
        'contenido_texto',
        'variables_usadas',
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
        'variables_usadas' => '[]',
        'metadata' => '{}',
    ];

    /**
     * Configuración de logs de actividad
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'asunto', 'es_activa'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Plantilla de email {$eventName}");
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
        return $this->hasMany(Campana::class, 'plantilla_email_id');
    }

    /**
     * Procesar el contenido HTML con las variables del usuario
     *
     * @param User $user
     * @return string
     */
    public function procesarContenido(User $user): string
    {
        $contenido = $this->contenido_html;
        
        // Reemplazar variables del usuario
        $variables = $this->obtenerVariables($user);
        
        foreach ($variables as $key => $value) {
            $contenido = str_replace('{{' . $key . '}}', $value ?? '', $contenido);
        }
        
        return $contenido;
    }

    /**
     * Procesar el asunto con las variables del usuario
     *
     * @param User $user
     * @return string
     */
    public function procesarAsunto(User $user): string
    {
        $asunto = $this->asunto;
        
        // Reemplazar variables del usuario
        $variables = $this->obtenerVariables($user);
        
        foreach ($variables as $key => $value) {
            $asunto = str_replace('{{' . $key . '}}', $value ?? '', $asunto);
        }
        
        return $asunto;
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
     * Extraer variables usadas del contenido
     *
     * @return array
     */
    public function extraerVariables(): array
    {
        $patron = '/\{\{([^}]+)\}\}/';
        $variables = [];
        
        // Buscar en el asunto
        preg_match_all($patron, $this->asunto, $matchesAsunto);
        $variables = array_merge($variables, $matchesAsunto[1] ?? []);
        
        // Buscar en el contenido HTML
        preg_match_all($patron, $this->contenido_html, $matchesContenido);
        $variables = array_merge($variables, $matchesContenido[1] ?? []);
        
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
}