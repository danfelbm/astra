<?php

namespace Modules\Proyectos\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para autoguardado de contratos (validación ligera)
 */
class AutosaveContratoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        // Para crear borradores, necesita permisos de creación
        // Para actualizar, necesita permisos de edición
        if ($this->route('contrato')) {
            return $this->user()->can('contratos.edit');
        }

        return $this->user()->can('contratos.create');
    }

    /**
     * Reglas de validación ligeras para autoguardado.
     */
    public function rules(): array
    {
        return [
            // Solo validaciones básicas, no requerimos campos obligatorios en borrador
            'proyecto_id' => 'nullable|exists:proyectos,id',
            'nombre' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'estado' => 'nullable|in:borrador,activo,finalizado,cancelado',
            'tipo' => 'nullable|in:servicio,obra,suministro,consultoria,otro',
            'monto_total' => 'nullable|numeric|min:0|max:999999999999.99',
            'moneda' => 'nullable|string|size:3',
            'responsable_id' => 'nullable|exists:users,id',
            'contraparte_user_id' => 'nullable|exists:users,id',
            'participantes' => 'nullable|array',
            'contraparte_nombre' => 'nullable|string|max:255',
            'contraparte_identificacion' => 'nullable|string|max:50',
            'contraparte_email' => 'nullable|email|max:255',
            'contraparte_telefono' => 'nullable|string|max:50',
            'archivos_paths' => 'nullable|array|max:10',
            'archivos_paths.*' => 'nullable|string',
            'archivos_nombres' => 'nullable|array',
            'archivos_nombres.*' => 'nullable|string|max:255',
            'tipos_archivos' => 'nullable|array',
            'observaciones' => 'nullable|string|max:5000',
            'campos_personalizados' => 'nullable|array',
        ];
    }

    /**
     * Prepara los datos para la validación.
     */
    protected function prepareForValidation(): void
    {
        // Decodificar arrays de archivos si vienen como JSON strings
        $data = [];

        if ($this->has('archivos_paths') && is_string($this->input('archivos_paths'))) {
            $data['archivos_paths'] = json_decode($this->input('archivos_paths'), true) ?: [];
        }

        if ($this->has('archivos_nombres') && is_string($this->input('archivos_nombres'))) {
            $data['archivos_nombres'] = json_decode($this->input('archivos_nombres'), true) ?: [];
        }

        if ($this->has('tipos_archivos') && is_string($this->input('tipos_archivos'))) {
            $data['tipos_archivos'] = json_decode($this->input('tipos_archivos'), true) ?: [];
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }
}