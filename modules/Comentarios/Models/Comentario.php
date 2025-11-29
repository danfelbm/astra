<?php

namespace Modules\Comentarios\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasTenant;
use Modules\Core\Traits\HasAuditLog;
use Carbon\Carbon;

class Comentario extends Model
{
    use HasTenant, HasAuditLog, SoftDeletes;

    protected $table = 'comentarios';

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'parent_id',
        'nivel',
        'contenido',
        'contenido_plain',
        'archivos_paths',
        'archivos_nombres',
        'archivos_tipos',
        'total_archivos',
        'quoted_comentario_id',
        'es_editado',
        'editado_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'nivel' => 'integer',
        'es_editado' => 'boolean',
        'editado_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'archivos_paths' => 'array',
        'archivos_nombres' => 'array',
        'archivos_tipos' => 'array',
        'total_archivos' => 'integer',
    ];

    /**
     * Atributos computados a incluir en JSON.
     */
    protected $appends = [
        'fecha_relativa',
        'es_editable',
        'es_eliminable',
        'tiempo_restante_edicion',
        'reacciones_resumen',
        'contenido_truncado',
        'archivos_info',
    ];

    /**
     * Emojis permitidos para reacciones.
     */
    public const EMOJIS = [
        'thumbs_up' => '👍',
        'thumbs_down' => '👎',
        'heart' => '❤️',
        'laugh' => '😄',
        'clap' => '👏',
        'fire' => '🔥',
        'check' => '✅',
        'eyes' => '👀',
    ];

    /**
     * Horas límite para edición de comentarios.
     */
    public const HORAS_EDICION = 24;

    /**
     * Máximo de archivos adjuntos por comentario.
     */
    public const MAX_ARCHIVOS = 3;

    /**
     * Extensiones de imagen permitidas.
     */
    public const EXTENSIONES_IMAGEN = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * Relación polimórfica: el modelo al que pertenece este comentario.
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Comentario padre (si es una respuesta).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Respuestas directas (solo para conteo, sin eager loading).
     */
    public function respuestasDirectas(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Respuestas con relaciones cargadas, SIN recursividad automática.
     * La profundidad se controla desde el Repository.
     */
    public function respuestasLimitadas(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->with([
                'autor:id,name,email',
                'reacciones',
                'comentarioCitado.autor:id,name,email',
            ])
            ->withCount('respuestasDirectas as total_respuestas_anidadas')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Comentario citado (quote).
     */
    public function comentarioCitado(): BelongsTo
    {
        return $this->belongsTo(self::class, 'quoted_comentario_id');
    }

    /**
     * Usuario que creó el comentario.
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario que actualizó el comentario.
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Reacciones (emojis) del comentario.
     */
    public function reacciones(): HasMany
    {
        return $this->hasMany(ComentarioReaccion::class, 'comentario_id');
    }

    /**
     * Menciones en el comentario.
     */
    public function menciones(): HasMany
    {
        return $this->hasMany(ComentarioMencion::class, 'comentario_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Solo comentarios raíz (sin padre).
     */
    public function scopeRaiz($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Comentarios ordenados por más recientes.
     */
    public function scopeRecientes($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Comentarios de un modelo específico.
     */
    public function scopeParaModelo($query, Model $model)
    {
        return $query
            ->where('commentable_type', get_class($model))
            ->where('commentable_id', $model->getKey());
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Contenido sin HTML (texto plano).
     */
    public function getContenidoPlainAttribute(): string
    {
        // Verificar si existe el atributo antes de acceder
        if (isset($this->attributes['contenido_plain']) && $this->attributes['contenido_plain']) {
            return $this->attributes['contenido_plain'];
        }
        return strip_tags($this->contenido ?? '');
    }

    /**
     * Contenido truncado para previews.
     */
    public function getContenidoTruncadoAttribute(): string
    {
        return Str::limit($this->contenido_plain, 150);
    }

    /**
     * Fecha relativa (hace 2 horas, ayer, etc).
     */
    public function getFechaRelativaAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Fecha formateada.
     */
    public function getFechaFormateadaAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    /**
     * Determina si el comentario puede ser editado.
     * Solo dentro de las primeras 24 horas y por el autor.
     */
    public function getEsEditableAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // Solo el autor puede editar
        if ($this->created_by !== auth()->id()) {
            return false;
        }

        // Solo dentro de las primeras 24 horas
        $horasTranscurridas = $this->created_at->diffInHours(now());
        return $horasTranscurridas < self::HORAS_EDICION;
    }

    /**
     * Tiempo restante para editar (en minutos).
     */
    public function getTiempoRestanteEdicionAttribute(): ?int
    {
        if (!$this->es_editable) {
            return null;
        }

        $limite = $this->created_at->addHours(self::HORAS_EDICION);
        return now()->diffInMinutes($limite, false);
    }

    /**
     * Determina si el comentario puede ser eliminado por el usuario actual.
     */
    public function getEsEliminableAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // El autor siempre puede eliminar su comentario
        if ($this->created_by === $user->id) {
            return true;
        }

        // Admins con permiso pueden eliminar cualquier comentario
        return $user->can('comentarios.delete_any');
    }

    /**
     * Resumen de reacciones agrupadas por emoji.
     * Optimizado: Usa valor pre-calculado en SQL si está disponible.
     */
    public function getReaccionesResumenAttribute(): array
    {
        // Si tenemos el valor optimizado calculado por el Repository, usarlo
        if (isset($this->attributes['reacciones_resumen_optimizado'])) {
            return $this->attributes['reacciones_resumen_optimizado'];
        }

        // Fallback: Calcular en PHP (para casos donde no viene del Repository)
        if (!$this->relationLoaded('reacciones')) {
            return [];
        }

        return $this->reacciones
            ->groupBy('emoji')
            ->map(function ($group, $emoji) {
                return [
                    'emoji' => $emoji,
                    'simbolo' => self::EMOJIS[$emoji] ?? $emoji,
                    'count' => $group->count(),
                    'usuarios' => $group->pluck('user_id')->toArray(),
                    'usuario_actual_reacciono' => auth()->check()
                        ? $group->contains('user_id', auth()->id())
                        : false,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Información completa de archivos adjuntos con URLs.
     */
    public function getArchivosInfoAttribute(): array
    {
        $paths = $this->archivos_paths ?? [];
        $nombres = $this->archivos_nombres ?? [];
        $tipos = $this->archivos_tipos ?? [];

        if (empty($paths)) {
            return [];
        }

        return collect($paths)->map(function ($path, $index) use ($nombres, $tipos) {
            $nombre = $nombres[$index] ?? basename($path);
            $tipo = $tipos[$index] ?? 'application/octet-stream';
            $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

            return [
                'path' => $path,
                'nombre' => $nombre,
                'tipo' => $tipo,
                'extension' => $extension,
                'url' => "/storage/{$path}",
                'es_imagen' => in_array($extension, self::EXTENSIONES_IMAGEN),
            ];
        })->values()->toArray();
    }

    /**
     * Indica si el comentario tiene archivos adjuntos.
     */
    public function getTieneArchivosAttribute(): bool
    {
        return ($this->total_archivos ?? 0) > 0;
    }

    // =========================================================================
    // MÉTODOS
    // =========================================================================

    /**
     * Verifica si el usuario actual puede editar este comentario.
     */
    public function puedeSerEditadoPor(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // Solo el autor puede editar
        if ($this->created_by !== $user->id) {
            return false;
        }

        // Solo dentro de las primeras 24 horas
        return $this->created_at->diffInHours(now()) < self::HORAS_EDICION;
    }

    /**
     * Verifica si el usuario actual puede eliminar este comentario.
     */
    public function puedeSerEliminadoPor(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        // El autor siempre puede eliminar
        if ($this->created_by === $user->id) {
            return true;
        }

        // Admins con permiso especial
        return $user->can('comentarios.delete_any');
    }

    /**
     * Marca el comentario como editado.
     */
    public function marcarComoEditado(): void
    {
        $this->update([
            'es_editado' => true,
            'editado_at' => now(),
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Extrae menciones (@usuario) del contenido.
     */
    public function extraerMenciones(): array
    {
        preg_match_all('/@(\w+)/', strip_tags($this->contenido), $matches);
        return $matches[1] ?? [];
    }

    /**
     * Genera el contenido plain a partir del HTML.
     */
    public function generarContenidoPlain(): void
    {
        $this->contenido_plain = strip_tags($this->contenido);
        $this->saveQuietly();
    }

    /**
     * Verifica si se pueden agregar más archivos.
     */
    public function puedeAgregarArchivos(int $cantidad = 1): bool
    {
        return ($this->total_archivos + $cantidad) <= self::MAX_ARCHIVOS;
    }

    /**
     * Establece los archivos adjuntos del comentario.
     */
    public function setArchivos(array $archivos): void
    {
        $paths = [];
        $nombres = [];
        $tipos = [];

        foreach ($archivos as $archivo) {
            if (isset($archivo['path'])) {
                $paths[] = $archivo['path'];
                $nombres[] = $archivo['name'] ?? basename($archivo['path']);
                $tipos[] = $archivo['mime_type'] ?? 'application/octet-stream';
            }
        }

        $this->archivos_paths = $paths;
        $this->archivos_nombres = $nombres;
        $this->archivos_tipos = $tipos;
        $this->total_archivos = count($paths);
    }

    /**
     * Obtiene las rutas de archivos que fueron eliminados al comparar con nuevos archivos.
     */
    public function getArchivosEliminados(array $nuevosArchivos): array
    {
        $pathsActuales = $this->archivos_paths ?? [];
        $nuevasPaths = collect($nuevosArchivos)->pluck('path')->toArray();

        return array_diff($pathsActuales, $nuevasPaths);
    }

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        // Al crear, calcular nivel y generar contenido plain
        static::creating(function (Comentario $comentario) {
            if ($comentario->parent_id) {
                $parent = self::find($comentario->parent_id);
                $comentario->nivel = $parent ? $parent->nivel + 1 : 0;
            }

            // Generar contenido plain
            if (empty($comentario->contenido_plain) && !empty($comentario->contenido)) {
                $comentario->contenido_plain = strip_tags($comentario->contenido);
            }

            // Asignar created_by si no está definido
            if (empty($comentario->created_by) && auth()->check()) {
                $comentario->created_by = auth()->id();
            }
        });

        // Al actualizar, regenerar contenido plain si cambió el contenido
        static::updating(function (Comentario $comentario) {
            if ($comentario->isDirty('contenido')) {
                $comentario->contenido_plain = strip_tags($comentario->contenido);
            }
        });
    }
}
