<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\Contrato;
use Modules\Proyectos\Models\CampoPersonalizado;
use Modules\Proyectos\Models\ValorCampoPersonalizado;
use Modules\Proyectos\Repositories\ContratoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class ContratoService
{
    public function __construct(
        private ContratoRepository $repository
    ) {}

    /**
     * Crea un nuevo contrato con sus campos personalizados.
     */
    public function create(array $data): array
    {
        DB::beginTransaction();
        try {
            // Separar campos personalizados y participantes del resto de datos
            $camposPersonalizados = $data['campos_personalizados'] ?? [];
            $participantes = $data['participantes'] ?? [];
            unset($data['campos_personalizados']);
            unset($data['participantes']);

            // Manejar archivo PDF si existe (retrocompatibilidad)
            if (isset($data['archivo_pdf']) && $data['archivo_pdf'] instanceof UploadedFile) {
                $data['archivo_pdf'] = $this->guardarArchivoPdf($data['archivo_pdf']);
            }

            // Manejar múltiples archivos si existen
            if (!empty($data['archivos_paths']) && is_array($data['archivos_paths'])) {
                // Los archivos ya vienen como paths porque fueron subidos por FileUploadField
                $data['total_archivos'] = count($data['archivos_paths']);

                // Establecer archivo_pdf con el primero para retrocompatibilidad
                if (!isset($data['archivo_pdf']) && !empty($data['archivos_paths'])) {
                    $data['archivo_pdf'] = $data['archivos_paths'][0];
                }
            }

            // Si se asignó un usuario del sistema como contraparte, limpiar campos de texto
            if (isset($data['contraparte_user_id']) && $data['contraparte_user_id']) {
                $data['contraparte_nombre'] = null;
                $data['contraparte_identificacion'] = null;
                $data['contraparte_email'] = null;
                $data['contraparte_telefono'] = null;
            }

            // Agregar información de auditoría
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            // Crear el contrato
            $contrato = $this->repository->create($data);

            // Guardar campos personalizados si existen
            if (!empty($camposPersonalizados)) {
                $this->guardarCamposPersonalizados($contrato, $camposPersonalizados);
            }

            // Guardar participantes si existen
            if (!empty($participantes)) {
                $this->sincronizarParticipantes($contrato, $participantes);
            }

            DB::commit();

            // Registrar en el log de actividad
            activity()
                ->performedOn($contrato)
                ->causedBy(auth()->user())
                ->log("Contrato '{$contrato->nombre}' creado");

            return [
                'success' => true,
                'contrato' => $contrato->fresh(['camposPersonalizados', 'proyecto', 'responsable']),
                'message' => 'Contrato creado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear contrato: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al crear el contrato: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza un contrato existente.
     */
    public function update(Contrato $contrato, array $data): array
    {
        DB::beginTransaction();
        try {
            // Separar campos personalizados y participantes del resto de datos
            $camposPersonalizados = $data['campos_personalizados'] ?? [];
            $participantes = $data['participantes'] ?? [];
            unset($data['campos_personalizados']);
            unset($data['participantes']);

            // Manejar archivo PDF si existe (retrocompatibilidad)
            if (isset($data['archivo_pdf']) && $data['archivo_pdf'] instanceof UploadedFile) {
                // Eliminar archivo anterior si existe
                if ($contrato->archivo_pdf) {
                    Storage::disk('public')->delete($contrato->archivo_pdf);
                }
                $data['archivo_pdf'] = $this->guardarArchivoPdf($data['archivo_pdf']);
            }

            // Manejar múltiples archivos si existen
            if (!empty($data['archivos_paths']) && is_array($data['archivos_paths'])) {
                // Los archivos ya vienen como paths porque fueron subidos por FileUploadField
                $data['total_archivos'] = count($data['archivos_paths']);

                // Establecer archivo_pdf con el primero para retrocompatibilidad
                if (!isset($data['archivo_pdf']) && !empty($data['archivos_paths'])) {
                    $data['archivo_pdf'] = $data['archivos_paths'][0];
                }
            }

            // Si se asignó un usuario del sistema como contraparte, limpiar campos de texto
            if (isset($data['contraparte_user_id']) && $data['contraparte_user_id']) {
                $data['contraparte_nombre'] = null;
                $data['contraparte_identificacion'] = null;
                $data['contraparte_email'] = null;
                $data['contraparte_telefono'] = null;
            }

            // Agregar información de auditoría
            $data['updated_by'] = auth()->id();

            // Actualizar el contrato
            $this->repository->update($contrato, $data);

            // Actualizar campos personalizados si existen
            if (!empty($camposPersonalizados)) {
                $this->guardarCamposPersonalizados($contrato, $camposPersonalizados);
            }

            // Sincronizar participantes
            $this->sincronizarParticipantes($contrato, $participantes);

            DB::commit();

            // Registrar en el log de actividad
            activity()
                ->performedOn($contrato)
                ->causedBy(auth()->user())
                ->log("Contrato '{$contrato->nombre}' actualizado");

            return [
                'success' => true,
                'contrato' => $contrato->fresh(['camposPersonalizados', 'proyecto', 'responsable']),
                'message' => 'Contrato actualizado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar contrato: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al actualizar el contrato: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina un contrato.
     */
    public function delete(Contrato $contrato): array
    {
        DB::beginTransaction();
        try {
            // Verificar si el contrato está activo
            if ($contrato->estado === 'activo') {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar un contrato activo. Debe finalizarlo o cancelarlo primero.'
                ];
            }

            // Eliminar archivos físicos si existen
            if ($contrato->archivos_paths && is_array($contrato->archivos_paths)) {
                foreach ($contrato->archivos_paths as $path) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            } elseif ($contrato->archivo_pdf) {
                // Retrocompatibilidad
                if (Storage::disk('public')->exists($contrato->archivo_pdf)) {
                    Storage::disk('public')->delete($contrato->archivo_pdf);
                }
            }

            $nombreContrato = $contrato->nombre;
            $resultado = $this->repository->delete($contrato);

            DB::commit();

            // Registrar en el log de actividad
            activity()
                ->causedBy(auth()->user())
                ->log("Contrato '{$nombreContrato}' eliminado");

            return [
                'success' => $resultado,
                'message' => $resultado ? 'Contrato eliminado exitosamente' : 'Error al eliminar el contrato'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar contrato: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al eliminar el contrato: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cambia el estado de un contrato.
     */
    public function cambiarEstado(Contrato $contrato, string $nuevoEstado): array
    {
        DB::beginTransaction();
        try {
            $estadoAnterior = $contrato->estado;

            if (!$contrato->cambiarEstado($nuevoEstado)) {
                return [
                    'success' => false,
                    'message' => 'No se puede cambiar del estado ' . $estadoAnterior . ' a ' . $nuevoEstado
                ];
            }

            DB::commit();

            // Registrar en el log de actividad
            activity()
                ->performedOn($contrato)
                ->causedBy(auth()->user())
                ->withProperties([
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $nuevoEstado
                ])
                ->log("Estado del contrato cambiado de '{$estadoAnterior}' a '{$nuevoEstado}'");

            return [
                'success' => true,
                'contrato' => $contrato->fresh(),
                'message' => 'Estado del contrato actualizado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al cambiar estado del contrato: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Duplica un contrato como plantilla.
     */
    public function duplicarContrato(Contrato $contrato): array
    {
        DB::beginTransaction();
        try {
            $nuevoContrato = $contrato->duplicar();

            DB::commit();

            // Registrar en el log de actividad
            activity()
                ->performedOn($nuevoContrato)
                ->causedBy(auth()->user())
                ->log("Contrato duplicado desde '{$contrato->nombre}'");

            return [
                'success' => true,
                'contrato' => $nuevoContrato->fresh(['camposPersonalizados']),
                'message' => 'Contrato duplicado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al duplicar contrato: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al duplicar el contrato: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Guarda los campos personalizados de un contrato.
     */
    protected function guardarCamposPersonalizados(Contrato $contrato, array $camposPersonalizados): void
    {
        foreach ($camposPersonalizados as $campoId => $valor) {
            // Verificar que el campo existe, está activo y aplica a contratos
            $campo = CampoPersonalizado::where('id', $campoId)
                ->where('activo', true)
                ->whereJsonContains('aplicar_para', 'contratos')
                ->first();

            if (!$campo) {
                continue;
            }

            // Manejar archivos si el campo es de tipo file
            if ($campo->tipo === 'file' && $valor instanceof UploadedFile) {
                $valor = $this->guardarArchivoCampo($valor, $contrato->id, $campoId);
            }

            // Guardar o actualizar el valor
            ValorCampoPersonalizado::updateOrCreate(
                [
                    'entidad_id' => $contrato->id,
                    'entidad_tipo' => 'contrato',
                    'campo_personalizado_id' => $campoId
                ],
                ['valor' => $valor]
            );
        }
    }

    /**
     * Guarda un archivo PDF del contrato.
     */
    protected function guardarArchivoPdf(UploadedFile $archivo): string
    {
        $nombreArchivo = 'contrato_' . time() . '_' . $archivo->getClientOriginalName();
        return $archivo->storeAs('contratos/pdfs', $nombreArchivo, 'local');
    }

    /**
     * Guarda un archivo de campo personalizado.
     */
    protected function guardarArchivoCampo(UploadedFile $archivo, int $contratoId, int $campoId): string
    {
        $nombreArchivo = "contrato_{$contratoId}_campo_{$campoId}_" . time() . '_' . $archivo->getClientOriginalName();
        return $archivo->storeAs('contratos/campos', $nombreArchivo, 'local');
    }

    /**
     * Genera un reporte de contratos.
     */
    public function generarReporte(array $filtros = []): array
    {
        try {
            $query = Contrato::query()
                ->with(['proyecto', 'responsable', 'camposPersonalizados.campoPersonalizado']);

            // Aplicar filtros
            if (isset($filtros['proyecto_id'])) {
                $query->where('proyecto_id', $filtros['proyecto_id']);
            }

            if (isset($filtros['estado'])) {
                $query->where('estado', $filtros['estado']);
            }

            if (isset($filtros['tipo'])) {
                $query->where('tipo', $filtros['tipo']);
            }

            if (isset($filtros['fecha_inicio'])) {
                $query->where('fecha_inicio', '>=', $filtros['fecha_inicio']);
            }

            if (isset($filtros['fecha_fin'])) {
                $query->where('fecha_fin', '<=', $filtros['fecha_fin']);
            }

            if (isset($filtros['responsable_id'])) {
                $query->where('responsable_id', $filtros['responsable_id']);
            }

            $contratos = $query->get();

            // Calcular estadísticas
            $estadisticas = [
                'total_contratos' => $contratos->count(),
                'contratos_activos' => $contratos->where('estado', 'activo')->count(),
                'contratos_vencidos' => $contratos->filter->esta_vencido->count(),
                'contratos_proximos_vencer' => $contratos->filter->esta_proximo_vencer->count(),
                'monto_total' => $contratos->sum('monto_total'),
                'por_estado' => $contratos->groupBy('estado')->map->count(),
                'por_tipo' => $contratos->groupBy('tipo')->map->count(),
            ];

            return [
                'success' => true,
                'contratos' => $contratos,
                'estadisticas' => $estadisticas
            ];
        } catch (\Exception $e) {
            Log::error('Error al generar reporte de contratos: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene contratos próximos a vencer.
     */
    public function obtenerProximosVencer(int $dias = 30): array
    {
        try {
            $contratos = Contrato::proximosVencer($dias)
                ->with(['proyecto', 'responsable'])
                ->orderBy('fecha_fin')
                ->get();

            return [
                'success' => true,
                'contratos' => $contratos,
                'total' => $contratos->count()
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener contratos próximos a vencer: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al obtener contratos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene contratos vencidos.
     */
    public function obtenerVencidos(): array
    {
        try {
            $contratos = Contrato::vencidos()
                ->with(['proyecto', 'responsable'])
                ->orderBy('fecha_fin', 'desc')
                ->get();

            return [
                'success' => true,
                'contratos' => $contratos,
                'total' => $contratos->count()
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener contratos vencidos: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al obtener contratos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sincroniza los participantes del contrato.
     */
    private function sincronizarParticipantes(Contrato $contrato, array $participantes): void
    {
        // Preparar datos para sincronización
        $participantesSync = [];

        foreach ($participantes as $participante) {
            if (isset($participante['user_id'])) {
                $participantesSync[$participante['user_id']] = [
                    'rol' => $participante['rol'] ?? 'participante',
                    'notas' => $participante['notas'] ?? null,
                ];
            }
        }

        // Sincronizar relación many-to-many
        $contrato->participantes()->sync($participantesSync);
    }

    /**
     * Obtiene las obligaciones de un contrato con estadísticas.
     */
    public function obtenerObligacionesConEstadisticas(Contrato $contrato): array
    {
        try {
            $obligaciones = $contrato->obligaciones()
                ->with(['responsable', 'hijos'])
                ->orderBy('orden')
                ->get();

            $todasObligaciones = $contrato->todasLasObligaciones()->get();

            $estadisticas = [
                'total' => $todasObligaciones->count(),
                'cumplidas' => $todasObligaciones->where('estado', 'cumplida')->count(),
                'pendientes' => $todasObligaciones->whereIn('estado', ['pendiente', 'en_progreso'])->count(),
                'vencidas' => $todasObligaciones->where('estado', 'vencida')->count(),
                'porcentaje_cumplimiento' => $todasObligaciones->count() > 0
                    ? round(($todasObligaciones->where('estado', 'cumplida')->count() / $todasObligaciones->count()) * 100)
                    : 0
            ];

            return [
                'success' => true,
                'obligaciones' => $obligaciones,
                'estadisticas' => $estadisticas
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener obligaciones del contrato: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al obtener las obligaciones: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina un archivo específico de un contrato.
     */
    public function eliminarArchivo(Contrato $contrato, int $indice): array
    {
        DB::beginTransaction();
        try {
            // Verificar que el índice es válido
            if (!isset($contrato->archivos_paths[$indice])) {
                return [
                    'success' => false,
                    'message' => 'El archivo especificado no existe'
                ];
            }

            // Usar el método del modelo para eliminar
            $resultado = $contrato->eliminarArchivoPorIndice($indice);

            if (!$resultado) {
                return [
                    'success' => false,
                    'message' => 'No se pudo eliminar el archivo'
                ];
            }

            DB::commit();

            // Registrar en el log de actividad
            activity()
                ->performedOn($contrato)
                ->causedBy(auth()->user())
                ->log("Archivo eliminado del contrato '{$contrato->nombre}'");

            return [
                'success' => true,
                'message' => 'Archivo eliminado exitosamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar archivo del contrato: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al eliminar el archivo: ' . $e->getMessage()
            ];
        }
    }
}