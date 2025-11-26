<?php

namespace Modules\Proyectos\Services;

use Modules\Proyectos\Models\CampoPersonalizado;
use Modules\Proyectos\Models\ValorCampoPersonalizado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CampoPersonalizadoService
{
    /**
     * Crea un nuevo campo personalizado.
     */
    public function create(array $data): CampoPersonalizado
    {
        DB::beginTransaction();
        try {
            // Asegurar que el slug sea único
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['nombre']);
            }

            // Si no se especifica orden, obtener el máximo + 1
            if (!isset($data['orden'])) {
                $data['orden'] = CampoPersonalizado::max('orden') + 1;
            }

            $campo = CampoPersonalizado::create($data);

            DB::commit();

            return $campo;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear campo personalizado: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza un campo personalizado existente.
     */
    public function update(CampoPersonalizado $campo, array $data): bool
    {
        DB::beginTransaction();
        try {
            // Si cambia el slug, asegurar que sea único
            if (isset($data['slug']) && $data['slug'] !== $campo->slug) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], $campo->id);
            }

            // Si cambia el tipo, verificar compatibilidad con valores existentes
            if (isset($data['tipo']) && $data['tipo'] !== $campo->tipo) {
                if (!$this->verificarCompatibilidadTipo($campo, $data['tipo'])) {
                    throw new \Exception('El nuevo tipo de campo no es compatible con los valores existentes');
                }
            }

            $result = $campo->update($data);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar campo personalizado: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Elimina un campo personalizado (sin valores asociados).
     */
    public function delete(CampoPersonalizado $campo): bool
    {
        DB::beginTransaction();
        try {
            // Verificar si tiene valores asociados
            if ($campo->valores()->exists()) {
                throw new \Exception('No se puede eliminar el campo porque tiene valores asociados a proyectos');
            }

            $result = $campo->delete();

            // Reordenar campos restantes
            $this->reordenarCampos();

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar campo personalizado: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Elimina un campo personalizado junto con todos sus valores asociados.
     *
     * @param CampoPersonalizado $campo Campo a eliminar
     * @param bool $force Si es true, elimina aunque tenga valores asociados
     * @return array{success: bool, message: string, deleted_values_count: int}
     * @throws \Exception Si force=false y tiene valores
     */
    public function deleteWithValues(CampoPersonalizado $campo, bool $force = false): array
    {
        DB::beginTransaction();
        try {
            $valoresCount = $campo->valores()->count();

            // Si no es forzado y tiene valores, lanzar excepción
            if (!$force && $valoresCount > 0) {
                throw new \Exception(
                    "No se puede eliminar el campo porque tiene {$valoresCount} valores asociados"
                );
            }

            // Guardar información para el log antes de eliminar
            $campoNombre = $campo->nombre;
            $campoSlug = $campo->slug;
            $campoId = $campo->id;

            // Registrar actividad ANTES de eliminar (con contexto completo)
            if ($valoresCount > 0) {
                activity()
                    ->performedOn($campo)
                    ->withProperties([
                        'campo_id' => $campoId,
                        'campo_nombre' => $campoNombre,
                        'campo_slug' => $campoSlug,
                        'valores_eliminados' => $valoresCount,
                        'force_delete' => true,
                        'user_id' => auth()->id(),
                        'user_email' => auth()->user()?->email,
                    ])
                    ->log("Campo personalizado '{$campoNombre}' eliminado con {$valoresCount} valores asociados (eliminación forzada)");
            }

            // Eliminar campo (CASCADE en BD eliminará automáticamente los valores)
            $campo->delete();

            // Reordenar campos restantes
            $this->reordenarCampos();

            DB::commit();

            return [
                'success' => true,
                'message' => $valoresCount > 0
                    ? "Campo '{$campoNombre}' eliminado junto con {$valoresCount} valores asociados"
                    : "Campo '{$campoNombre}' eliminado exitosamente",
                'deleted_values_count' => $valoresCount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar campo personalizado: ' . $e->getMessage(), [
                'campo_id' => $campo->id ?? null,
                'force' => $force,
            ]);
            throw $e;
        }
    }

    /**
     * Genera un slug único para el campo.
     */
    private function generateUniqueSlug(string $nombre, int $excludeId = null): string
    {
        $slug = Str::slug($nombre, '_');
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '_' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Verifica si un slug ya existe.
     */
    private function slugExists(string $slug, int $excludeId = null): bool
    {
        $query = CampoPersonalizado::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Verifica si un cambio de tipo es compatible con valores existentes.
     */
    private function verificarCompatibilidadTipo(CampoPersonalizado $campo, string $nuevoTipo): bool
    {
        // Si no hay valores, siempre es compatible
        if (!$campo->valores()->exists()) {
            return true;
        }

        $tipoActual = $campo->tipo;

        // Matriz de compatibilidad de tipos
        $compatibilidades = [
            'text' => ['textarea', 'select', 'radio'],
            'textarea' => ['text'],
            'number' => [],
            'date' => [],
            'select' => ['text', 'textarea', 'radio'],
            'radio' => ['text', 'textarea', 'select'],
            'checkbox' => [],
            'file' => []
        ];

        return in_array($nuevoTipo, $compatibilidades[$tipoActual] ?? []);
    }

    /**
     * Reordena todos los campos activos.
     */
    public function reordenarCampos(): void
    {
        $campos = CampoPersonalizado::orderBy('orden')->get();

        foreach ($campos as $index => $campo) {
            $campo->update(['orden' => $index]);
        }
    }

    /**
     * Actualiza el orden de múltiples campos.
     */
    public function actualizarOrden(array $ordenCampos): void
    {
        DB::beginTransaction();
        try {
            foreach ($ordenCampos as $item) {
                CampoPersonalizado::where('id', $item['id'])
                    ->update(['orden' => $item['orden']]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar orden de campos: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Clona campos personalizados de otro tenant.
     */
    public function clonarCamposDesde(int $tenantOrigenId): array
    {
        DB::beginTransaction();
        try {
            $camposOrigen = CampoPersonalizado::where('tenant_id', $tenantOrigenId)
                ->orderBy('orden')
                ->get();

            $camposClonados = [];

            foreach ($camposOrigen as $campoOrigen) {
                $datosNuevoCampo = $campoOrigen->toArray();

                // Remover ID y timestamps
                unset($datosNuevoCampo['id']);
                unset($datosNuevoCampo['created_at']);
                unset($datosNuevoCampo['updated_at']);

                // Asignar nuevo tenant
                $datosNuevoCampo['tenant_id'] = auth()->user()->tenant_id;

                // Asegurar slug único
                $datosNuevoCampo['slug'] = $this->generateUniqueSlug($datosNuevoCampo['nombre']);

                $nuevoCampo = CampoPersonalizado::create($datosNuevoCampo);
                $camposClonados[] = $nuevoCampo;
            }

            DB::commit();

            return $camposClonados;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al clonar campos personalizados: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Exporta la configuración de campos personalizados.
     */
    public function exportarConfiguracion(): array
    {
        $campos = CampoPersonalizado::ordenado()->get();

        return $campos->map(function ($campo) {
            return [
                'nombre' => $campo->nombre,
                'slug' => $campo->slug,
                'tipo' => $campo->tipo,
                'opciones' => $campo->opciones,
                'es_requerido' => $campo->es_requerido,
                'orden' => $campo->orden,
                'descripcion' => $campo->descripcion,
                'placeholder' => $campo->placeholder,
                'validacion' => $campo->validacion,
            ];
        })->toArray();
    }

    /**
     * Importa configuración de campos personalizados.
     */
    public function importarConfiguracion(array $configuracion): array
    {
        DB::beginTransaction();
        try {
            $camposImportados = [];

            foreach ($configuracion as $config) {
                // Asegurar slug único
                $config['slug'] = $this->generateUniqueSlug($config['nombre']);

                // Agregar tenant si aplica
                if (auth()->user()->tenant_id) {
                    $config['tenant_id'] = auth()->user()->tenant_id;
                }

                $campo = CampoPersonalizado::create($config);
                $camposImportados[] = $campo;
            }

            DB::commit();

            return $camposImportados;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al importar configuración de campos: ' . $e->getMessage());
            throw $e;
        }
    }
}