<?php

namespace App\Models\Core;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserUpdateRequest extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'user_id',
        'new_email',
        'new_telefono',
        'documentos_soporte',
        'current_email',
        'current_telefono',
        'status',
        'admin_id',
        'admin_notes',
        'approved_at',
        'rejected_at',
        'tenant_id',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'documentos_soporte' => 'array',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /**
     * Relación con el usuario solicitante
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el administrador que procesó la solicitud
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Verifica si la solicitud está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verifica si la solicitud fue aprobada
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Verifica si la solicitud fue rechazada
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Aprueba la solicitud y actualiza los datos del usuario
     */
    public function approve(User $admin, ?string $notes = null): bool
    {
        \Log::info('[UserUpdateRequest::approve] INICIO', [
            'id' => $this->id,
            'status' => $this->status,
            'admin_id' => $admin->id
        ]);

        if (!$this->isPending()) {
            \Log::warning('[UserUpdateRequest::approve] No está pendiente', [
                'id' => $this->id,
                'status' => $this->status
            ]);
            return false;
        }

        try {
            // Iniciar transacción
            return \DB::transaction(function () use ($admin, $notes) {
                \Log::info('[UserUpdateRequest::approve] Iniciando transacción', [
                    'id' => $this->id
                ]);

                // Actualizar el registro de solicitud
                $updated = $this->update([
                    'status' => 'approved',
                    'admin_id' => $admin->id,
                    'admin_notes' => $notes,
                    'approved_at' => now(),
                ]);

                \Log::info('[UserUpdateRequest::approve] Estado actualizado', [
                    'id' => $this->id,
                    'updated' => $updated,
                    'new_status' => $this->fresh()->status
                ]);

                // Actualizar datos del usuario
                $updateData = [];
                
                if ($this->new_email && $this->new_email !== $this->current_email) {
                    $updateData['email'] = $this->new_email;
                    \Log::info('[UserUpdateRequest::approve] Actualizando email', [
                        'user_id' => $this->user_id,
                        'old' => $this->current_email,
                        'new' => $this->new_email
                    ]);
                }
                
                if ($this->new_telefono && $this->new_telefono !== $this->current_telefono) {
                    $updateData['telefono'] = $this->new_telefono;
                    \Log::info('[UserUpdateRequest::approve] Actualizando teléfono', [
                        'user_id' => $this->user_id,
                        'old' => $this->current_telefono,
                        'new' => $this->new_telefono
                    ]);
                }
                
                if (!empty($updateData)) {
                    $userUpdated = $this->user->update($updateData);
                    \Log::info('[UserUpdateRequest::approve] Usuario actualizado', [
                        'user_id' => $this->user_id,
                        'updated' => $userUpdated,
                        'data' => $updateData
                    ]);
                }
                
                \Log::info('[UserUpdateRequest::approve] Transacción completada', [
                    'id' => $this->id
                ]);
                
                return true;
            });
        } catch (\Exception $e) {
            \Log::error('[UserUpdateRequest::approve] ERROR', [
                'id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Rechaza la solicitud
     */
    public function reject(User $admin, ?string $notes = null): bool
    {
        \Log::info('[UserUpdateRequest::reject] INICIO', [
            'id' => $this->id,
            'status' => $this->status,
            'admin_id' => $admin->id
        ]);

        if (!$this->isPending()) {
            \Log::warning('[UserUpdateRequest::reject] No está pendiente', [
                'id' => $this->id,
                'status' => $this->status
            ]);
            return false;
        }

        try {
            $updated = $this->update([
                'status' => 'rejected',
                'admin_id' => $admin->id,
                'admin_notes' => $notes,
                'rejected_at' => now(),
            ]);

            \Log::info('[UserUpdateRequest::reject] Estado actualizado', [
                'id' => $this->id,
                'updated' => $updated,
                'new_status' => $this->fresh()->status
            ]);

            return $updated;
        } catch (\Exception $e) {
            \Log::error('[UserUpdateRequest::reject] ERROR', [
                'id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtiene las URLs públicas de los documentos soporte
     */
    public function getDocumentUrls(): array
    {
        if (empty($this->documentos_soporte)) {
            return [];
        }

        return array_map(function ($path) {
            return Storage::disk('public')->url($path);
        }, $this->documentos_soporte);
    }

    /**
     * Verifica si hay cambios solicitados
     */
    public function hasDataChanges(): bool
    {
        $hasEmailChange = $this->new_email && $this->new_email !== $this->current_email;
        $hasPhoneChange = $this->new_telefono && $this->new_telefono !== $this->current_telefono;
        
        return $hasEmailChange || $hasPhoneChange;
    }

    /**
     * Obtiene un resumen de los cambios solicitados
     */
    public function getChangesSummary(): array
    {
        $changes = [];
        
        if ($this->new_email && $this->new_email !== $this->current_email) {
            $changes['email'] = [
                'current' => $this->current_email,
                'new' => $this->new_email,
            ];
        }
        
        if ($this->new_telefono && $this->new_telefono !== $this->current_telefono) {
            $changes['telefono'] = [
                'current' => $this->current_telefono,
                'new' => $this->new_telefono,
            ];
        }
        
        return $changes;
    }

    /**
     * Scope para solicitudes pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para solicitudes aprobadas
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope para solicitudes rechazadas
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope para solicitudes con documentos
     */
    public function scopeWithDocuments($query)
    {
        return $query->whereNotNull('documentos_soporte')
            ->where('documentos_soporte', '!=', '[]');
    }

    /**
     * Verifica si el usuario tiene solicitudes pendientes
     */
    public static function userHasPendingRequest(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->pending()
            ->exists();
    }

    /**
     * Elimina los archivos asociados cuando se elimina la solicitud
     */
    protected static function booted(): void
    {
        static::deleting(function ($request) {
            // Eliminar archivos del storage
            if (!empty($request->documentos_soporte)) {
                foreach ($request->documentos_soporte as $path) {
                    Storage::disk('public')->delete($path);
                }
            }
        });
    }

    /**
     * Obtiene información de los archivos adjuntos
     */
    public function getDocumentInfo(): array
    {
        if (empty($this->documentos_soporte)) {
            return [];
        }

        return array_map(function ($path) {
            $exists = Storage::disk('public')->exists($path);
            return [
                'path' => $path,
                'url' => $exists ? Storage::disk('public')->url($path) : null,
                'name' => basename($path),
                'exists' => $exists,
                'size' => $exists ? Storage::disk('public')->size($path) : 0,
                'mime_type' => $exists ? Storage::disk('public')->mimeType($path) : null,
            ];
        }, $this->documentos_soporte);
    }
}