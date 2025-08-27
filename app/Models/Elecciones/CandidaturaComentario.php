<?php

namespace App\Models\Elecciones;

use App\Models\Core\User;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CandidaturaComentario extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'candidatura_comentarios';

    protected $fillable = [
        'candidatura_id',
        'version_candidatura',
        'comentario',
        'tipo',
        'enviado_por_email',
        'created_by',
    ];

    protected $casts = [
        'version_candidatura' => 'integer',
        'enviado_por_email' => 'boolean',
    ];

    // Tipos de comentario
    const TIPO_GENERAL = 'general';
    const TIPO_APROBACION = 'aprobacion';
    const TIPO_RECHAZO = 'rechazo';
    const TIPO_BORRADOR = 'borrador';
    const TIPO_NOTA_ADMIN = 'nota_admin';

    // Relaciones
    public function candidatura(): BelongsTo
    {
        return $this->belongsTo(Candidatura::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePorVersion(Builder $query, int $version): Builder
    {
        return $query->where('version_candidatura', $version);
    }

    public function scopeUltimoComentario(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit(1);
    }

    public function scopeTipoGeneral(Builder $query): Builder
    {
        return $query->whereIn('tipo', [self::TIPO_GENERAL, self::TIPO_NOTA_ADMIN]);
    }

    public function scopeNotificables(Builder $query): Builder
    {
        return $query->where('tipo', '!=', self::TIPO_NOTA_ADMIN);
    }

    public function scopeEnviados(Builder $query): Builder
    {
        return $query->where('enviado_por_email', true);
    }

    public function scopeRecientes(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // Métodos de utilidad
    
    /**
     * Obtener el comentario como texto plano (sin HTML)
     */
    public function getComentarioPlainAttribute(): string
    {
        return strip_tags($this->comentario);
    }

    /**
     * Obtener el comentario truncado
     */
    public function getComentarioTruncadoAttribute(): string
    {
        $plain = $this->comentario_plain;
        return Str::limit($plain, 100);
    }

    /**
     * Obtener label del tipo
     */
    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            self::TIPO_GENERAL => 'Comentario General',
            self::TIPO_APROBACION => 'Aprobación',
            self::TIPO_RECHAZO => 'Rechazo',
            self::TIPO_BORRADOR => 'Vuelta a Borrador',
            self::TIPO_NOTA_ADMIN => 'Nota Administrativa',
            default => 'Desconocido',
        };
    }

    /**
     * Obtener color del tipo para badges
     */
    public function getTipoColorAttribute(): string
    {
        return match($this->tipo) {
            self::TIPO_GENERAL => 'bg-blue-100 text-blue-800',
            self::TIPO_APROBACION => 'bg-green-100 text-green-800',
            self::TIPO_RECHAZO => 'bg-red-100 text-red-800',
            self::TIPO_BORRADOR => 'bg-yellow-100 text-yellow-800',
            self::TIPO_NOTA_ADMIN => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Obtener icono del tipo
     */
    public function getTipoIconAttribute(): string
    {
        return match($this->tipo) {
            self::TIPO_GENERAL => 'message-circle',
            self::TIPO_APROBACION => 'check-circle',
            self::TIPO_RECHAZO => 'x-circle',
            self::TIPO_BORRADOR => 'rotate-ccw',
            self::TIPO_NOTA_ADMIN => 'sticky-note',
            default => 'message-square',
        };
    }

    /**
     * Determinar si el comentario debe notificarse por email
     */
    public function debeNotificar(): bool
    {
        return $this->tipo !== self::TIPO_NOTA_ADMIN;
    }

    /**
     * Marcar como enviado por email
     */
    public function marcarComoEnviado(): bool
    {
        return $this->update(['enviado_por_email' => true]);
    }

    /**
     * Obtener fecha formateada
     */
    public function getFechaFormateadaAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    /**
     * Obtener fecha relativa
     */
    public function getFechaRelativaAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Método estático para crear comentario
     */
    public static function crearComentario(
        Candidatura $candidatura,
        string $comentario,
        string $tipo = self::TIPO_GENERAL,
        ?User $usuario = null,
        bool $enviarEmail = false
    ): self {
        $comentarioObj = self::create([
            'candidatura_id' => $candidatura->id,
            'version_candidatura' => $candidatura->version,
            'comentario' => $comentario,
            'tipo' => $tipo,
            'enviado_por_email' => $enviarEmail,
            'created_by' => $usuario?->id ?? auth()->id(),
        ]);

        // Actualizar el campo comentarios_admin de la candidatura con el último comentario
        // para mantener compatibilidad con el sistema actual
        if ($tipo !== self::TIPO_NOTA_ADMIN) {
            $candidatura->update(['comentarios_admin' => $comentario]);
        }

        return $comentarioObj;
    }

    /**
     * Obtener el último comentario de una candidatura
     */
    public static function obtenerUltimoComentario(int $candidaturaId, ?int $version = null): ?self
    {
        $query = self::where('candidatura_id', $candidaturaId);
        
        if ($version !== null) {
            $query->where('version_candidatura', $version);
        }
        
        return $query->orderBy('created_at', 'desc')->first();
    }

    /**
     * Obtener historial de comentarios agrupados por versión
     */
    public static function obtenerHistorialPorVersion(int $candidaturaId): \Illuminate\Support\Collection
    {
        return self::where('candidatura_id', $candidaturaId)
            ->with('createdBy:id,name,email')
            ->orderBy('version_candidatura', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('version_candidatura');
    }

    /**
     * Contar comentarios de una candidatura
     */
    public static function contarComentarios(int $candidaturaId, ?string $tipo = null): int
    {
        $query = self::where('candidatura_id', $candidaturaId);
        
        if ($tipo) {
            $query->where('tipo', $tipo);
        }
        
        return $query->count();
    }
}