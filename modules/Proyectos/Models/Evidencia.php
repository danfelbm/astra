<?php

namespace Modules\Proyectos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Traits\HasTenant;
use Modules\Core\Traits\HasAuditLog;
use Modules\Comentarios\Traits\HasComentarios;
use Modules\Core\Models\User;
use Illuminate\Support\Facades\Storage;

class Evidencia extends Model
{
    use HasTenant, HasAuditLog, HasComentarios;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'evidencias';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'obligacion_id',
        'user_id',
        'tipo_evidencia',
        'archivo_path',
        'archivo_nombre',
        'archivos_paths',
        'archivos_nombres',
        'total_archivos',
        'descripcion',
        'metadata',
        'tipos_archivos',
        'estado',
        'observaciones_admin',
        'revisado_at',
        'revisado_por',
        'tenant_id',
        'created_by',
        'updated_by'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
        'archivos_paths' => 'array',
        'archivos_nombres' => 'array',
        'tipos_archivos' => 'array',
        'revisado_at' => 'datetime',
    ];

    /**
     * Los atributos que deben ser anexados al array/JSON del modelo.
     *
     * @var array
     */
    protected $appends = [
        'tipo_evidencia_label',
        'estado_label',
        'estado_color',
        'archivo_url',
        'archivo_size_formatted',
        'archivos_urls',
        'archivos_info',
        'es_imagen',
        'es_video',
        'es_audio',
        'es_documento',
        'tiene_multiples_archivos',
        'archivos_por_tipo',
        'total_comentarios'
    ];

    /**
     * Obtiene la obligación a la que pertenece la evidencia.
     */
    public function obligacion(): BelongsTo
    {
        return $this->belongsTo(ObligacionContrato::class, 'obligacion_id');
    }

    /**
     * Obtiene el usuario que subió la evidencia.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtiene el usuario que revisó la evidencia.
     */
    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    /**
     * Obtiene el usuario que creó la evidencia.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtiene el usuario que actualizó la evidencia.
     */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtiene los entregables asociados a la evidencia.
     */
    public function entregables(): BelongsToMany
    {
        return $this->belongsToMany(Entregable::class, 'evidencia_entregable')
                    ->withTimestamps();
    }

    /**
     * Obtiene la etiqueta del tipo de evidencia.
     */
    public function getTipoEvidenciaLabelAttribute(): string
    {
        $labels = [
            'imagen' => 'Imagen',
            'video' => 'Video',
            'audio' => 'Audio',
            'documento' => 'Documento'
        ];

        return $labels[$this->tipo_evidencia] ?? $this->tipo_evidencia;
    }

    /**
     * Obtiene la etiqueta del estado.
     */
    public function getEstadoLabelAttribute(): string
    {
        $labels = [
            'pendiente' => 'Pendiente',
            'aprobada' => 'Aprobada',
            'rechazada' => 'Rechazada'
        ];

        return $labels[$this->estado] ?? $this->estado;
    }

    /**
     * Obtiene el color asociado al estado.
     */
    public function getEstadoColorAttribute(): string
    {
        $colors = [
            'pendiente' => 'yellow',
            'aprobada' => 'green',
            'rechazada' => 'red'
        ];

        return $colors[$this->estado] ?? 'gray';
    }

    /**
     * Obtiene la URL del archivo.
     */
    public function getArchivoUrlAttribute(): ?string
    {
        if (!$this->archivo_path) {
            return null;
        }

        // Si es una URL completa, devolverla tal cual
        if (filter_var($this->archivo_path, FILTER_VALIDATE_URL)) {
            return $this->archivo_path;
        }

        // Si no, generar la URL usando Storage
        return Storage::disk('public')->url($this->archivo_path);
    }

    /**
     * Obtiene el tamaño del archivo formateado.
     */
    public function getArchivoSizeFormattedAttribute(): ?string
    {
        if (!$this->metadata || !isset($this->metadata['size'])) {
            return null;
        }

        $size = $this->metadata['size'];

        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2) . ' KB';
        } elseif ($size < 1073741824) {
            return round($size / 1048576, 2) . ' MB';
        } else {
            return round($size / 1073741824, 2) . ' GB';
        }
    }

    /**
     * Verifica si es una imagen.
     */
    public function getEsImagenAttribute(): bool
    {
        return $this->tipo_evidencia === 'imagen';
    }

    /**
     * Verifica si es un video.
     */
    public function getEsVideoAttribute(): bool
    {
        return $this->tipo_evidencia === 'video';
    }

    /**
     * Verifica si es un audio.
     */
    public function getEsAudioAttribute(): bool
    {
        return $this->tipo_evidencia === 'audio';
    }

    /**
     * Verifica si es un documento.
     */
    public function getEsDocumentoAttribute(): bool
    {
        return $this->tipo_evidencia === 'documento';
    }

    /**
     * Obtiene las URLs de todos los archivos.
     */
    public function getArchivosUrlsAttribute(): array
    {
        $urls = [];

        // Si hay archivos múltiples, usar esos
        if ($this->archivos_paths && is_array($this->archivos_paths)) {
            foreach ($this->archivos_paths as $path) {
                if (filter_var($path, FILTER_VALIDATE_URL)) {
                    $urls[] = $path;
                } else {
                    $urls[] = Storage::disk('public')->url($path);
                }
            }
        }
        // Si no hay archivos múltiples pero hay archivo_path, usar ese (retrocompatibilidad)
        elseif ($this->archivo_path) {
            if (filter_var($this->archivo_path, FILTER_VALIDATE_URL)) {
                $urls[] = $this->archivo_path;
            } else {
                $urls[] = Storage::disk('public')->url($this->archivo_path);
            }
        }

        return $urls;
    }

    /**
     * Obtiene información completa de todos los archivos.
     */
    public function getArchivosInfoAttribute(): array
    {
        $archivos = [];

        // Si hay archivos múltiples
        if ($this->archivos_paths && is_array($this->archivos_paths)) {
            $nombres = $this->archivos_nombres ?? [];
            foreach ($this->archivos_paths as $index => $path) {
                $nombre = $nombres[$index] ?? basename($path);
                $archivos[] = [
                    'path' => $path,
                    'nombre' => $nombre,
                    'url' => filter_var($path, FILTER_VALIDATE_URL) ? $path : Storage::disk('public')->url($path),
                    'indice' => $index
                ];
            }
        }
        // Retrocompatibilidad con archivo único
        elseif ($this->archivo_path) {
            $archivos[] = [
                'path' => $this->archivo_path,
                'nombre' => $this->archivo_nombre ?? basename($this->archivo_path),
                'url' => filter_var($this->archivo_path, FILTER_VALIDATE_URL) ? $this->archivo_path : Storage::disk('public')->url($this->archivo_path),
                'indice' => 0
            ];
        }

        return $archivos;
    }

    /**
     * Verifica si tiene múltiples archivos.
     */
    public function getTieneMultiplesArchivosAttribute(): bool
    {
        return $this->total_archivos > 1;
    }

    /**
     * Marca la evidencia como aprobada.
     */
    public function aprobar(int $userId = null, string $observaciones = null): void
    {
        $estadoAnterior = $this->estado;
        $this->estado = 'aprobada';
        $this->observaciones_admin = $observaciones;
        $this->revisado_at = now();
        $this->revisado_por = $userId ?? auth()->id();
        $this->save();

        // Registrar cambio de estado en audit log
        $this->logStateChange('estado', $estadoAnterior, 'aprobada', $observaciones);
    }

    /**
     * Marca la evidencia como rechazada.
     */
    public function rechazar(int $userId = null, string $observaciones = null): void
    {
        $estadoAnterior = $this->estado;
        $this->estado = 'rechazada';
        $this->observaciones_admin = $observaciones;
        $this->revisado_at = now();
        $this->revisado_por = $userId ?? auth()->id();
        $this->save();

        // Registrar cambio de estado en audit log
        $this->logStateChange('estado', $estadoAnterior, 'rechazada', $observaciones);
    }

    /**
     * Agrega archivos a la evidencia.
     */
    public function agregarArchivos(array $archivos): void
    {
        $paths = $this->archivos_paths ?? [];
        $nombres = $this->archivos_nombres ?? [];

        foreach ($archivos as $archivo) {
            $paths[] = $archivo['path'];
            $nombres[] = $archivo['nombre'];
        }

        $this->archivos_paths = $paths;
        $this->archivos_nombres = $nombres;
        $this->total_archivos = count($paths);
        $this->save();
    }

    /**
     * Reemplaza todos los archivos de la evidencia.
     */
    public function reemplazarArchivos(array $archivos): void
    {
        $paths = [];
        $nombres = [];

        foreach ($archivos as $archivo) {
            $paths[] = $archivo['path'];
            $nombres[] = $archivo['nombre'];
        }

        $this->archivos_paths = $paths;
        $this->archivos_nombres = $nombres;
        $this->total_archivos = count($paths);
        $this->save();
    }

    /**
     * Elimina un archivo específico por índice.
     */
    public function eliminarArchivoPorIndice(int $indice): bool
    {
        $paths = $this->archivos_paths ?? [];
        $nombres = $this->archivos_nombres ?? [];

        if (!isset($paths[$indice])) {
            return false;
        }

        // Eliminar el archivo del storage
        $path = $paths[$indice];
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        // Remover de los arrays
        unset($paths[$indice]);
        unset($nombres[$indice]);

        // Reindexar arrays
        $this->archivos_paths = array_values($paths);
        $this->archivos_nombres = array_values($nombres);
        $this->total_archivos = count($this->archivos_paths);
        $this->save();

        return true;
    }

    /**
     * Valida el límite máximo de archivos.
     */
    public function puedeAgregarMasArchivos(int $cantidad = 1): bool
    {
        $totalActual = $this->total_archivos ?? 0;
        return ($totalActual + $cantidad) <= 10; // Máximo 10 archivos
    }

    /**
     * Scope para evidencias pendientes.
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para evidencias aprobadas.
     */
    public function scopeAprobadas($query)
    {
        return $query->where('estado', 'aprobada');
    }

    /**
     * Scope para evidencias rechazadas.
     */
    public function scopeRechazadas($query)
    {
        return $query->where('estado', 'rechazada');
    }

    /**
     * Scope para evidencias del usuario actual.
     */
    public function scopeMisEvidencias($query)
    {
        if (auth()->check()) {
            return $query->where('user_id', auth()->id());
        }
        return $query;
    }

    /**
     * Scope para evidencias por tipo.
     */
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo_evidencia', $tipo);
    }

    /**
     * Verifica si el usuario puede editar esta evidencia.
     */
    public function puedeSerEditadaPor(User $user): bool
    {
        // Solo puede editar si es el propietario y está pendiente
        if ($this->user_id === $user->id && $this->estado === 'pendiente') {
            return true;
        }

        // O si tiene permisos de editar cualquier evidencia
        if ($user->can('evidencias.edit')) {
            return true;
        }

        return false;
    }

    /**
     * Verifica si el usuario puede eliminar esta evidencia.
     */
    public function puedeSerEliminadaPor(User $user): bool
    {
        // Solo puede eliminar si es el propietario y está pendiente
        if ($this->user_id === $user->id && $this->estado === 'pendiente') {
            return true;
        }

        // O si tiene permisos de eliminar cualquier evidencia
        if ($user->can('evidencias.delete')) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene extensiones permitidas por tipo de evidencia.
     */
    public static function getExtensionesPermitidas(string $tipo): array
    {
        $extensiones = [
            'imagen' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'webm', 'mkv'],
            'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'wma'],
            'documento' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf']
        ];

        return $extensiones[$tipo] ?? [];
    }

    /**
     * Obtiene MIME types permitidos por tipo de evidencia.
     */
    public static function getMimeTypesPermitidos(string $tipo): array
    {
        $mimeTypes = [
            'imagen' => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml'
            ],
            'video' => [
                'video/mp4',
                'video/avi',
                'video/quicktime',
                'video/x-ms-wmv',
                'video/webm',
                'video/x-matroska'
            ],
            'audio' => [
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                'audio/mp4',
                'audio/aac',
                'audio/x-ms-wma'
            ],
            'documento' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/rtf'
            ]
        ];

        return $mimeTypes[$tipo] ?? [];
    }

    /**
     * Obtiene los archivos agrupados por tipo.
     *
     * @return array
     */
    public function getArchivosPorTipoAttribute(): array
    {
        $agrupados = [
            'imagen' => ['paths' => [], 'nombres' => [], 'count' => 0],
            'video' => ['paths' => [], 'nombres' => [], 'count' => 0],
            'audio' => ['paths' => [], 'nombres' => [], 'count' => 0],
            'documento' => ['paths' => [], 'nombres' => [], 'count' => 0],
        ];

        // Si no hay tipos_archivos, usar tipo_evidencia por defecto para todos
        if (empty($this->tipos_archivos) || !is_array($this->tipos_archivos)) {
            // Todos los archivos son del mismo tipo (compatibilidad hacia atrás)
            $tipo = $this->tipo_evidencia;
            $agrupados[$tipo]['paths'] = $this->archivos_paths ?? [];
            $agrupados[$tipo]['nombres'] = $this->archivos_nombres ?? [];
            $agrupados[$tipo]['count'] = count($this->archivos_paths ?? []);
        } else {
            // Usar el mapeo de tipos_archivos
            $paths = $this->archivos_paths ?? [];
            $nombres = $this->archivos_nombres ?? [];

            foreach ($paths as $index => $path) {
                $tipo = $this->tipos_archivos[$path] ?? $this->tipo_evidencia;
                $agrupados[$tipo]['paths'][] = $path;
                $agrupados[$tipo]['nombres'][] = $nombres[$index] ?? basename($path);
                $agrupados[$tipo]['count']++;
            }
        }

        return $agrupados;
    }

    /**
     * Calcula el tipo dominante basado en la mayoría de archivos.
     *
     * @return string
     */
    public function calcularTipoDominante(): string
    {
        if (empty($this->tipos_archivos)) {
            return $this->tipo_evidencia;
        }

        $conteo = array_count_values($this->tipos_archivos);
        arsort($conteo);

        return key($conteo) ?: $this->tipo_evidencia;
    }

    /**
     * Detecta el tipo de un archivo basado en su MIME type.
     *
     * @param string $mimeType
     * @return string
     */
    public static function detectarTipoPorMime(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'imagen';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        // Por defecto, considerar como documento
        return 'documento';
    }
}