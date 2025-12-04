<?php

namespace Modules\Campanas\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Modelo para grupos de WhatsApp sincronizados desde Evolution API
 */
class WhatsAppGroup extends Model
{
    use HasFactory, LogsActivity;

    /**
     * La tabla asociada al modelo
     */
    protected $table = 'whatsapp_groups';

    /**
     * Los atributos asignables masivamente
     */
    protected $fillable = [
        'group_jid',
        'nombre',
        'descripcion',
        'tipo',
        'avatar_url',
        'participantes_count',
        'owner_jid',
        'is_announce',
        'is_restrict',
        'metadata',
        'synced_at',
    ];

    /**
     * Los atributos que deben ser convertidos
     */
    protected $casts = [
        'participantes_count' => 'integer',
        'is_announce' => 'boolean',
        'is_restrict' => 'boolean',
        'metadata' => 'array',
        'synced_at' => 'datetime',
    ];

    /**
     * Valores por defecto
     */
    protected $attributes = [
        'tipo' => 'grupo',
        'participantes_count' => 0,
        'is_announce' => false,
        'is_restrict' => false,
        'metadata' => '{}',
    ];

    /**
     * Configuración de logs de actividad
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'tipo'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Grupo de WhatsApp {$eventName}");
    }

    /**
     * Obtener las campañas que usan este grupo
     */
    public function campanas(): BelongsToMany
    {
        return $this->belongsToMany(Campana::class, 'campana_whatsapp_groups')
            ->withTimestamps();
    }

    /**
     * Crear o actualizar grupo desde datos de la API
     */
    public static function upsertFromApi(array $data): self
    {
        // Determinar tipo basado en tamaño (comunidades suelen tener más de 256 participantes)
        $participantes = $data['size'] ?? 0;
        $tipo = $participantes > 256 ? 'comunidad' : 'grupo';

        return self::updateOrCreate(
            ['group_jid' => $data['id']],
            [
                'nombre' => $data['subject'] ?? 'Sin nombre',
                'descripcion' => $data['desc'] ?? null,
                'tipo' => $tipo,
                'avatar_url' => $data['pictureUrl'] ?? null,
                'participantes_count' => $participantes,
                'owner_jid' => $data['owner'] ?? $data['subjectOwner'] ?? null,
                'is_announce' => $data['announce'] ?? false,
                'is_restrict' => $data['restrict'] ?? false,
                'metadata' => [
                    'creation' => $data['creation'] ?? null,
                    'subjectTime' => $data['subjectTime'] ?? null,
                    'descId' => $data['descId'] ?? null,
                ],
                'synced_at' => now(),
            ]
        );
    }

    /**
     * Scope para buscar por nombre
     */
    public function scopeBuscar($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('nombre', 'like', "%{$search}%")
              ->orWhere('group_jid', 'like', "%{$search}%")
              ->orWhere('descripcion', 'like', "%{$search}%");
        });
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeTipo($query, ?string $tipo)
    {
        if (!$tipo) {
            return $query;
        }

        return $query->where('tipo', $tipo);
    }

    /**
     * Obtener label del tipo
     */
    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'comunidad' => 'Comunidad',
            default => 'Grupo',
        };
    }

    /**
     * Verificar si es una comunidad
     */
    public function esComunidad(): bool
    {
        return $this->tipo === 'comunidad';
    }

    /**
     * Obtener el JID formateado para visualización
     */
    public function getJidCortoAttribute(): string
    {
        // Quitar @g.us para mostrar solo el número
        return str_replace('@g.us', '', $this->group_jid);
    }

    /**
     * Obtener fecha de última sincronización formateada
     */
    public function getUltimaSincronizacionAttribute(): ?string
    {
        if (!$this->synced_at) {
            return null;
        }

        return $this->synced_at->diffForHumans();
    }
}
