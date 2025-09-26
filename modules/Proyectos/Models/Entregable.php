<?php

namespace Modules\Proyectos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Traits\HasTenant;
use Modules\Core\Traits\HasAuditLog;
use Modules\Core\Models\User;

class Entregable extends Model
{
    use HasTenant, HasAuditLog;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'entregables';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'hito_id',
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'orden',
        'estado',
        'prioridad',
        'responsable_id',
        'completado_at',
        'completado_por',
        'notas_completado',
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
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
        'completado_at' => 'datetime',
        'orden' => 'integer',
    ];

    /**
     * Los atributos que deben ser anexados al array/JSON del modelo.
     *
     * @var array
     */
    protected $appends = [
        'estado_label',
        'estado_color',
        'prioridad_label',
        'prioridad_color',
        'dias_restantes',
        'esta_vencido',
        'esta_proximo_vencer',
        'duracion_dias'
    ];

    /**
     * Obtiene el hito al que pertenece el entregable.
     */
    public function hito(): BelongsTo
    {
        return $this->belongsTo(Hito::class);
    }

    /**
     * Obtiene el responsable principal del entregable.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Obtiene el usuario que completó el entregable.
     */
    public function completadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completado_por');
    }

    /**
     * Obtiene el usuario que creó el entregable.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtiene el usuario que actualizó el entregable.
     */
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtiene los usuarios asignados al entregable.
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'entregable_usuario')
                    ->withPivot('rol')
                    ->withTimestamps();
    }

    /**
     * Obtiene los usuarios responsables.
     */
    public function responsables(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'entregable_usuario')
                    ->wherePivot('rol', 'responsable')
                    ->withTimestamps();
    }

    /**
     * Obtiene los usuarios colaboradores.
     */
    public function colaboradores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'entregable_usuario')
                    ->wherePivot('rol', 'colaborador')
                    ->withTimestamps();
    }

    /**
     * Obtiene los usuarios revisores.
     */
    public function revisores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'entregable_usuario')
                    ->wherePivot('rol', 'revisor')
                    ->withTimestamps();
    }

    /**
     * Obtiene la etiqueta del estado.
     */
    public function getEstadoLabelAttribute(): string
    {
        $labels = [
            'pendiente' => 'Pendiente',
            'en_progreso' => 'En Progreso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado'
        ];

        return $labels[$this->estado] ?? $this->estado;
    }

    /**
     * Obtiene el color asociado al estado.
     */
    public function getEstadoColorAttribute(): string
    {
        $colors = [
            'pendiente' => 'gray',
            'en_progreso' => 'yellow',
            'completado' => 'green',
            'cancelado' => 'red'
        ];

        return $colors[$this->estado] ?? 'gray';
    }

    /**
     * Obtiene la etiqueta de prioridad.
     */
    public function getPrioridadLabelAttribute(): string
    {
        $labels = [
            'baja' => 'Baja',
            'media' => 'Media',
            'alta' => 'Alta'
        ];

        return $labels[$this->prioridad] ?? $this->prioridad;
    }

    /**
     * Obtiene el color asociado a la prioridad.
     */
    public function getPrioridadColorAttribute(): string
    {
        $colors = [
            'baja' => 'gray',
            'media' => 'blue',
            'alta' => 'red'
        ];

        return $colors[$this->prioridad] ?? 'gray';
    }

    /**
     * Calcula los días restantes hasta la fecha de fin.
     */
    public function getDiasRestantesAttribute(): ?int
    {
        if (!$this->fecha_fin) {
            return null;
        }

        if ($this->estado === 'completado' || $this->estado === 'cancelado') {
            return null;
        }

        $hoy = now()->startOfDay();
        $fin = $this->fecha_fin->startOfDay();

        return $fin->diffInDays($hoy, false) * -1; // Negativo si ya pasó
    }

    /**
     * Calcula la duración del entregable en días.
     */
    public function getDuracionDiasAttribute(): ?int
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return null;
        }

        return $this->fecha_inicio->diffInDays($this->fecha_fin);
    }

    /**
     * Verifica si el entregable está vencido.
     */
    public function getEstaVencidoAttribute(): bool
    {
        if ($this->estado === 'completado' || $this->estado === 'cancelado') {
            return false;
        }

        if (!$this->fecha_fin) {
            return false;
        }

        return $this->fecha_fin->isPast();
    }

    /**
     * Verifica si el entregable está próximo a vencer (3 días).
     */
    public function getEstaProximoVencerAttribute(): bool
    {
        if ($this->esta_vencido) {
            return false;
        }

        if ($this->estado === 'completado' || $this->estado === 'cancelado') {
            return false;
        }

        if (!$this->fecha_fin) {
            return false;
        }

        $diasRestantes = $this->dias_restantes;
        return $diasRestantes !== null && $diasRestantes > 0 && $diasRestantes <= 3;
    }

    /**
     * Marca el entregable como completado.
     */
    public function marcarComoCompletado(int $userId = null, string $notas = null): void
    {
        $this->estado = 'completado';
        $this->completado_at = now();
        $this->completado_por = $userId ?? auth()->id();
        $this->notas_completado = $notas;
        $this->save();

        // Actualizar el porcentaje del hito
        $this->hito->calcularPorcentajeCompletado();
    }

    /**
     * Marca el entregable como en progreso.
     */
    public function marcarComoEnProgreso(): void
    {
        if ($this->estado === 'pendiente') {
            $this->estado = 'en_progreso';
            $this->save();

            // Si el hito está pendiente, también lo ponemos en progreso
            if ($this->hito->estado === 'pendiente') {
                $this->hito->update(['estado' => 'en_progreso']);
            }
        }
    }

    /**
     * Asigna usuarios al entregable.
     */
    public function asignarUsuarios(array $usuariosConRoles): void
    {
        $syncData = [];

        // Verificar si el array viene con la estructura del frontend
        if (!empty($usuariosConRoles)) {
            $firstElement = reset($usuariosConRoles);

            // Si viene con estructura { user_id: X, rol: Y }
            if (is_array($firstElement) && isset($firstElement['user_id'])) {
                foreach ($usuariosConRoles as $usuario) {
                    if (isset($usuario['user_id']) && isset($usuario['rol'])) {
                        $syncData[$usuario['user_id']] = ['rol' => $usuario['rol']];
                    }
                }
            }
            // Si viene con estructura [userId => rol]
            else {
                foreach ($usuariosConRoles as $userId => $rol) {
                    $syncData[$userId] = ['rol' => $rol];
                }
            }
        }

        $this->usuarios()->sync($syncData);
    }

    /**
     * Scope para entregables pendientes.
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para entregables en progreso.
     */
    public function scopeEnProgreso($query)
    {
        return $query->where('estado', 'en_progreso');
    }

    /**
     * Scope para entregables completados.
     */
    public function scopeCompletados($query)
    {
        return $query->where('estado', 'completado');
    }

    /**
     * Scope para entregables vencidos.
     */
    public function scopeVencidos($query)
    {
        return $query->where('fecha_fin', '<', now())
                    ->whereNotIn('estado', ['completado', 'cancelado']);
    }

    /**
     * Scope para entregables del usuario actual.
     */
    public function scopeMisEntregables($query)
    {
        if (auth()->check()) {
            $userId = auth()->id();
            return $query->where(function ($q) use ($userId) {
                $q->where('responsable_id', $userId)
                  ->orWhereHas('usuarios', function ($q) use ($userId) {
                      $q->where('user_id', $userId);
                  });
            });
        }
        return $query;
    }

    /**
     * Scope para entregables de alta prioridad.
     */
    public function scopeAltaPrioridad($query)
    {
        return $query->where('prioridad', 'alta');
    }

    /**
     * Reordena los entregables dentro del hito.
     */
    public static function reordenar(int $hitoId, array $ordenIds): void
    {
        foreach ($ordenIds as $orden => $entregableId) {
            static::where('id', $entregableId)
                  ->where('hito_id', $hitoId)
                  ->update(['orden' => $orden + 1]);
        }
    }

    /**
     * Verifica si el usuario puede completar este entregable.
     */
    public function puedeSerCompletadoPor(User $user): bool
    {
        // Si es el responsable principal
        if ($this->responsable_id === $user->id) {
            return true;
        }

        // Si es un usuario asignado con rol de responsable
        if ($this->responsables()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Si tiene permisos de completar cualquier entregable
        if ($user->can('entregables.complete')) {
            return true;
        }

        return false;
    }
}