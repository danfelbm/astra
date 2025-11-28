<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\Evidencia;
use Modules\Proyectos\Models\Contrato;
use Modules\Proyectos\Repositories\EvidenciaRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EvidenciaService
{
    public function __construct(
        private EvidenciaRepository $repository
    ) {}

    /**
     * Crea una nueva evidencia.
     */
    public function create(array $data, Contrato $contrato): array
    {
        DB::beginTransaction();

        try {
            // Preparar datos para crear la evidencia
            $evidenciaData = [
                'obligacion_id' => $data['obligacion_id'],
                'user_id' => $data['user_id'] ?? auth()->id(),
                'tipo_evidencia' => $data['tipo_evidencia'],
                'descripcion' => $data['descripcion'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'tipos_archivos' => $data['tipos_archivos'] ?? null,
                'estado' => 'pendiente',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ];

            // Manejar archivos (múltiples o único)
            if (!empty($data['archivos_paths']) && is_array($data['archivos_paths'])) {
                // Múltiples archivos
                $evidenciaData['archivos_paths'] = $data['archivos_paths'];
                $evidenciaData['archivos_nombres'] = $data['archivos_nombres'] ?? array_map([$this, 'extractFileName'], $data['archivos_paths']);
                $evidenciaData['total_archivos'] = count($data['archivos_paths']);

                // Para retrocompatibilidad, establecer también archivo_path con el primero
                $evidenciaData['archivo_path'] = $data['archivos_paths'][0] ?? null;
                $evidenciaData['archivo_nombre'] = $evidenciaData['archivos_nombres'][0] ?? null;
            } else {
                // Un solo archivo (retrocompatibilidad)
                $evidenciaData['archivo_path'] = $data['archivo_path'] ?? null;
                $evidenciaData['archivo_nombre'] = $data['archivo_nombre'] ?? ($data['archivo_path'] ? $this->extractFileName($data['archivo_path']) : null);
                $evidenciaData['total_archivos'] = $data['archivo_path'] ? 1 : 0;

                // Si hay archivo único, establecer también en arrays para consistencia
                if ($data['archivo_path']) {
                    $evidenciaData['archivos_paths'] = [$data['archivo_path']];
                    $evidenciaData['archivos_nombres'] = [$evidenciaData['archivo_nombre']];
                }
            }

            // Crear la evidencia
            $evidencia = $this->repository->create($evidenciaData);

            // Vincular con entregables si se proporcionaron
            if (!empty($data['entregable_ids'])) {
                $evidencia->entregables()->sync($data['entregable_ids']);
            }

            // Log de actividad
            activity()
                ->performedOn($evidencia)
                ->causedBy(auth()->user())
                ->withProperties([
                    'contrato_id' => $contrato->id,
                    'obligacion_id' => $data['obligacion_id'],
                    'tipo' => $data['tipo_evidencia']
                ])
                ->log('Evidencia creada');

            // Limpiar borrador del cache del servidor
            $cacheKey = "evidencia_draft_{$contrato->id}_" . auth()->id();
            cache()->forget($cacheKey);

            DB::commit();

            return [
                'success' => true,
                'evidencia' => $evidencia->fresh(['obligacion', 'entregables']),
                'message' => 'Evidencia subida exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear evidencia', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'message' => 'Error al subir la evidencia: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza una evidencia existente.
     */
    public function update(Evidencia $evidencia, array $data): array
    {
        DB::beginTransaction();

        try {
            // Solo actualizar campos permitidos
            $updateData = [];

            if (isset($data['descripcion'])) {
                $updateData['descripcion'] = $data['descripcion'];
            }

            if (isset($data['tipos_archivos'])) {
                $updateData['tipos_archivos'] = $data['tipos_archivos'];
            }

            // Manejar archivos (múltiples o único)
            if (!empty($data['archivos_paths']) && is_array($data['archivos_paths'])) {
                // Múltiples archivos - eliminar archivos anteriores
                if ($evidencia->archivos_paths) {
                    foreach ($evidencia->archivos_paths as $oldPath) {
                        $this->deleteFile($oldPath);
                    }
                }

                $updateData['archivos_paths'] = $data['archivos_paths'];
                $updateData['archivos_nombres'] = $data['archivos_nombres'] ?? array_map([$this, 'extractFileName'], $data['archivos_paths']);
                $updateData['total_archivos'] = count($data['archivos_paths']);

                // Para retrocompatibilidad, establecer también archivo_path con el primero
                $updateData['archivo_path'] = $data['archivos_paths'][0] ?? null;
                $updateData['archivo_nombre'] = $updateData['archivos_nombres'][0] ?? null;

                // Actualizar metadata si se proporciona
                if (isset($data['metadata'])) {
                    $updateData['metadata'] = $data['metadata'];
                }
            } elseif (isset($data['archivo_path']) && $data['archivo_path'] !== $evidencia->archivo_path) {
                // Un solo archivo (retrocompatibilidad)
                // Eliminar archivo anterior si existe
                $this->deleteFile($evidencia->archivo_path);

                $updateData['archivo_path'] = $data['archivo_path'];
                $updateData['archivo_nombre'] = $data['archivo_nombre'] ?? $this->extractFileName($data['archivo_path']);
                $updateData['total_archivos'] = 1;

                // Establecer también en arrays para consistencia
                $updateData['archivos_paths'] = [$data['archivo_path']];
                $updateData['archivos_nombres'] = [$updateData['archivo_nombre']];

                // Actualizar metadata si se proporciona
                if (isset($data['metadata'])) {
                    $updateData['metadata'] = $data['metadata'];
                }
            }

            $updateData['updated_by'] = auth()->id();

            // Actualizar la evidencia
            $this->repository->update($evidencia, $updateData);

            // Actualizar entregables si se proporcionaron
            if (isset($data['entregable_ids'])) {
                $evidencia->entregables()->sync($data['entregable_ids']);
            }

            // Log de actividad
            activity()
                ->performedOn($evidencia)
                ->causedBy(auth()->user())
                ->withProperties(['cambios' => $updateData])
                ->log('Evidencia actualizada');

            DB::commit();

            return [
                'success' => true,
                'evidencia' => $evidencia->fresh(['obligacion', 'entregables']),
                'message' => 'Evidencia actualizada exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al actualizar evidencia', [
                'error' => $e->getMessage(),
                'evidencia_id' => $evidencia->id,
                'data' => $data
            ]);

            return [
                'success' => false,
                'message' => 'Error al actualizar la evidencia: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina una evidencia.
     */
    public function delete(Evidencia $evidencia): array
    {
        DB::beginTransaction();

        try {
            // Eliminar archivo físico
            $this->deleteFile($evidencia->archivo_path);

            // Log de actividad antes de eliminar
            activity()
                ->performedOn($evidencia)
                ->causedBy(auth()->user())
                ->withProperties([
                    'evidencia_id' => $evidencia->id,
                    'obligacion_id' => $evidencia->obligacion_id
                ])
                ->log('Evidencia eliminada');

            // Eliminar la evidencia
            $this->repository->delete($evidencia);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Evidencia eliminada exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al eliminar evidencia', [
                'error' => $e->getMessage(),
                'evidencia_id' => $evidencia->id
            ]);

            return [
                'success' => false,
                'message' => 'Error al eliminar la evidencia: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Aprueba una evidencia.
     */
    public function aprobar(Evidencia $evidencia, string $observaciones = null): array
    {
        DB::beginTransaction();

        try {
            $evidencia->aprobar(auth()->id(), $observaciones);

            // Log de actividad
            activity()
                ->performedOn($evidencia)
                ->causedBy(auth()->user())
                ->withProperties(['observaciones' => $observaciones])
                ->log('Evidencia aprobada');

            DB::commit();

            return [
                'success' => true,
                'evidencia' => $evidencia->fresh(),
                'message' => 'Evidencia aprobada exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al aprobar evidencia', [
                'error' => $e->getMessage(),
                'evidencia_id' => $evidencia->id
            ]);

            return [
                'success' => false,
                'message' => 'Error al aprobar la evidencia'
            ];
        }
    }

    /**
     * Rechaza una evidencia.
     */
    public function rechazar(Evidencia $evidencia, string $observaciones = null): array
    {
        DB::beginTransaction();

        try {
            $evidencia->rechazar(auth()->id(), $observaciones);

            // Log de actividad
            activity()
                ->performedOn($evidencia)
                ->causedBy(auth()->user())
                ->withProperties(['observaciones' => $observaciones])
                ->log('Evidencia rechazada');

            DB::commit();

            return [
                'success' => true,
                'evidencia' => $evidencia->fresh(),
                'message' => 'Evidencia rechazada'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al rechazar evidencia', [
                'error' => $e->getMessage(),
                'evidencia_id' => $evidencia->id
            ]);

            return [
                'success' => false,
                'message' => 'Error al rechazar la evidencia'
            ];
        }
    }

    /**
     * Procesa y guarda un archivo capturado (desde cámara/micrófono).
     */
    public function procesarCaptura(string $base64Data, string $tipo): array
    {
        try {
            // Extraer el tipo MIME y los datos
            if (preg_match('/^data:([^;]+);base64,(.+)$/', $base64Data, $matches)) {
                $mimeType = $matches[1];
                $data = base64_decode($matches[2]);

                // Determinar extensión por tipo MIME
                $extension = $this->getExtensionByMimeType($mimeType);

                // Generar nombre único
                $fileName = uniqid('captura_') . '.' . $extension;
                $path = "evidencias/{$tipo}/" . date('Y/m/d') . '/' . $fileName;

                // Guardar archivo
                Storage::disk('public')->put($path, $data);

                return [
                    'success' => true,
                    'path' => $path,
                    'nombre' => $fileName,
                    'metadata' => [
                        'mime_type' => $mimeType,
                        'size' => strlen($data),
                        'captured_at' => now()->toIso8601String()
                    ]
                ];
            }

            throw new \Exception('Formato de datos inválido');

        } catch (\Exception $e) {
            Log::error('Error al procesar captura', [
                'error' => $e->getMessage(),
                'tipo' => $tipo
            ]);

            return [
                'success' => false,
                'message' => 'Error al procesar la captura: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extrae el nombre del archivo de una ruta.
     */
    private function extractFileName(string $path): string
    {
        return basename($path);
    }

    /**
     * Elimina un archivo del storage.
     */
    private function deleteFile(string $path): void
    {
        try {
            // No eliminar si es una URL externa
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return;
            }

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo eliminar el archivo', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene la extensión por tipo MIME.
     */
    private function getExtensionByMimeType(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/webm' => 'webm',
            'application/pdf' => 'pdf'
        ];

        return $extensions[$mimeType] ?? 'bin';
    }
}