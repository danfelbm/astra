<?php

namespace App\Observers;

use App\Models\Candidatura;
use App\Models\CandidaturaHistorial;
use App\Models\CandidaturaConfig;
use Illuminate\Support\Facades\Auth;

class CandidaturaObserver
{
    /**
     * Handle the Candidatura "created" event.
     * Captura cuando se crea una nueva candidatura
     */
    public function created(Candidatura $candidatura): void
    {
        // Debug: Observer created ejecutado - candidatura_id: {$candidatura->id}
        
        // Crear entrada inicial en el historial cuando se crea la candidatura
        $this->crearEntradaHistorial($candidatura, 'Candidatura creada');
    }

    /**
     * Handle the Candidatura "updated" event.
     * Se ejecuta DESPUÉS de actualizar el modelo para capturar el estado ACTUAL
     */
    public function updated(Candidatura $candidatura): void
    {
        // Debug: Observer updated ejecutado - candidatura_id: {$candidatura->id}
        
        // NUEVA LÓGICA: Los autoguardados usan saveQuietly() y no disparan este Observer
        // Esta verificación es para otros casos donde se actualice ultimo_autoguardado manualmente
        
        // Verificación por cambios detectados - si solo cambió ultimo_autoguardado y formulario_data
        // pero no cambió estado, versión o comentarios, probablemente es un guardado menor
        $esGuardadoMenor = $candidatura->wasChanged('ultimo_autoguardado') && 
                          !$candidatura->wasChanged('estado') && 
                          !$candidatura->wasChanged('version') &&
                          !$candidatura->wasChanged('comentarios_admin');
        
        if ($esGuardadoMenor) {
            // No crear historial para guardados menores
            return;
        }
        
        // NUEVA LÓGICA: Capturar TODOS los cambios de estado y formulario
        
        $huboCambioEstado = $candidatura->wasChanged('estado');
        $huboCambioFormulario = $candidatura->wasChanged('formulario_data');
        
        // Debug: Detectando cambios - estado: {$huboCambioEstado}, formulario: {$huboCambioFormulario}
        
        // Solo proceder si hubo cambios relevantes
        if (!$huboCambioEstado && !$huboCambioFormulario) {
            // Debug: Sin cambios relevantes, saliendo
            return;
        }
        
        // Si hubo cambio en el formulario, resetear aprobaciones de campos que cambiaron
        if ($huboCambioFormulario) {
            $this->resetearAprobacionesCamposModificados($candidatura);
        }
        
        // Determinar motivo del cambio
        $motivoCambio = $this->determinarMotivoCambio($candidatura);
        
        // Debug: Creando historial - motivo: {$motivoCambio}
        
        // Crear entrada en historial
        $this->crearEntradaHistorial($candidatura, $motivoCambio);
    }

    /**
     * Crear entrada en el historial (método común para created y updated)
     */
    private function crearEntradaHistorial(Candidatura $candidatura, string $motivoCambio): void
    {
        // Debug: Crear entrada historial iniciado - candidatura_id: {$candidatura->id}
        
        $versionActual = $candidatura->version;
        
        // Obtener configuración para filtrar datos del formulario
        $config = CandidaturaConfig::obtenerConfiguracionActiva();
        $configuracionCampos = [];
        $formularioDataFiltrado = [];
        
        if ($config && $config->tieneCampos()) {
            $configuracionCampos = $config->obtenerCampos();
            
            // Si hay datos de formulario, filtrarlos según configuración actual
            if (!empty($candidatura->formulario_data)) {
                $camposValidos = collect($configuracionCampos)->pluck('id')->toArray();
                $formularioDataFiltrado = array_intersect_key(
                    $candidatura->formulario_data, 
                    array_flip($camposValidos)
                );
            }
        }
        
        // Verificar si ya existe un registro con esta combinación única
        $existingHistorial = CandidaturaHistorial::where('candidatura_id', $candidatura->id)
            ->where('version', $versionActual)
            ->where('estado_en_momento', $candidatura->estado)
            ->first();
        
        if ($existingHistorial) {
            // Debug: Historial ya existe, actualizando - historial_id: {$existingHistorial->id}
            
            // Actualizar el registro existente con los nuevos datos
            // Evitar concatenación repetitiva del mismo motivo
            $motivoActual = $existingHistorial->motivo_cambio;
            
            // Solo agregar el nuevo motivo si es diferente al último agregado
            if (!str_contains($motivoActual, $motivoCambio) || 
                !str_ends_with($motivoActual, $motivoCambio)) {
                $motivoActual = $motivoActual . ' | ' . $motivoCambio;
            }
            
            $existingHistorial->update([
                'formulario_data' => $formularioDataFiltrado,
                'configuracion_campos_en_momento' => $configuracionCampos,
                'comentarios_admin_en_momento' => $candidatura->comentarios_admin,
                'motivo_cambio' => $motivoActual,
                'updated_at' => now(),
            ]);
            
            // Debug: Historial actualizado exitosamente - historial_id: {$existingHistorial->id}
            
            return;
        }
        
        // Crear nueva entrada en historial si no existe
        try {
            $historialEntry = CandidaturaHistorial::create([
                'candidatura_id' => $candidatura->id,
                'version' => $versionActual,
                'formulario_data' => $formularioDataFiltrado,
                'configuracion_campos_en_momento' => $configuracionCampos,
                'estado_en_momento' => $candidatura->estado,
                'comentarios_admin_en_momento' => $candidatura->comentarios_admin,
                'created_by' => Auth::id(),
                'motivo_cambio' => $motivoCambio,
            ]);
            
            // Debug: Historial creado exitosamente - historial_id: {$historialEntry->id}
            
        } catch (\Exception $e) {
            \Log::error('❌ ERROR CREANDO HISTORIAL', [
                'candidatura_id' => $candidatura->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Si es un error de duplicado, solo loguear pero no lanzar excepción
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                \Log::warning('⚠️ Intento de crear historial duplicado ignorado', [
                    'candidatura_id' => $candidatura->id,
                    'version' => $versionActual,
                    'estado' => $candidatura->estado
                ]);
                return;
            }
            
            throw $e;
        }
    }

    /**
     * Determinar el motivo del cambio basado en qué campos se modificaron
     */
    private function determinarMotivoCambio(Candidatura $candidatura): string
    {
        $cambios = [];
        
        if ($candidatura->wasChanged('formulario_data')) {
            $cambios[] = 'datos del formulario';
        }
        
        if ($candidatura->wasChanged('estado')) {
            $cambios[] = "cambio de estado a '{$candidatura->estado}'";
        }
        
        if (empty($cambios)) {
            return 'Actualización general';
        }
        
        return 'Cambio en: ' . implode(', ', $cambios);
    }

    /**
     * Resetear aprobaciones de campos que fueron modificados
     */
    private function resetearAprobacionesCamposModificados(Candidatura $candidatura): void
    {
        // Obtener datos anteriores y nuevos del formulario
        $datosAnteriores = $candidatura->getOriginal('formulario_data') ?? [];
        $datosNuevos = $candidatura->formulario_data ?? [];
        
        // Si los datos son strings JSON, decodificarlos
        if (is_string($datosAnteriores)) {
            $datosAnteriores = json_decode($datosAnteriores, true) ?? [];
        }
        if (is_string($datosNuevos)) {
            $datosNuevos = json_decode($datosNuevos, true) ?? [];
        }
        
        // Identificar campos que cambiaron
        $camposModificados = [];
        foreach ($datosNuevos as $campoId => $valorNuevo) {
            $valorAnterior = $datosAnteriores[$campoId] ?? null;
            
            // Comparar valores serializados para manejar arrays
            $valorNuevoSerializado = is_array($valorNuevo) ? json_encode($valorNuevo) : $valorNuevo;
            $valorAnteriorSerializado = is_array($valorAnterior) ? json_encode($valorAnterior) : $valorAnterior;
            
            if ($valorNuevoSerializado !== $valorAnteriorSerializado) {
                $camposModificados[] = $campoId;
            }
        }
        
        // Si hay campos modificados, resetear sus aprobaciones
        if (!empty($camposModificados)) {
            // Debug: Resetando aprobaciones de campos modificados - candidatura_id: {$candidatura->id}
            
            // Resetear aprobaciones de los campos que cambiaron
            $candidatura->campoAprobaciones()
                ->whereIn('campo_id', $camposModificados)
                ->where('version_candidatura', $candidatura->version)
                ->each(function ($aprobacion) {
                    $aprobacion->resetearAprobacion();
                });
        }
    }
}
