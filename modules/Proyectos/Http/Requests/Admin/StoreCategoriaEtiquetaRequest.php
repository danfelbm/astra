<?php

namespace Modules\Proyectos\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaEtiquetaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return $this->user()->can('categorias_etiquetas.create');
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        $tenant_id = auth()->user()->tenant_id ?? null;

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                'min:2',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('categorias_etiquetas')->where(function ($query) use ($tenant_id) {
                    return $query->where('tenant_id', $tenant_id);
                }),
            ],
            'color' => [
                'required',
                'string',
                Rule::in([
                    'gray', 'red', 'orange', 'amber', 'yellow', 'lime', 'green',
                    'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet',
                    'purple', 'fuchsia', 'pink', 'rose'
                ]),
            ],
            'icono' => [
                'nullable',
                'string',
                'max:50',
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:500',
            ],
            'orden' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
            'activo' => [
                'nullable',
                'boolean',
            ],
            'aplicar_para' => [
                'nullable',
                'array',
            ],
            'aplicar_para.*' => [
                'string',
                Rule::in(['proyectos', 'hitos', 'entregables']),
            ],
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres',
            'nombre.max' => 'El nombre no puede exceder los 100 caracteres',
            'slug.unique' => 'Ya existe una categoría con este slug',
            'color.required' => 'Debe seleccionar un color para la categoría',
            'color.in' => 'El color seleccionado no es válido',
            'descripcion.max' => 'La descripción no puede exceder los 500 caracteres',
            'orden.min' => 'El orden debe ser un número positivo',
            'orden.max' => 'El orden no puede exceder 9999',
            'aplicar_para.array' => 'El campo aplicar_para debe ser un array',
            'aplicar_para.*.in' => 'La entidad seleccionada no es válida',
        ];
    }

    /**
     * Prepara los datos para la validación.
     */
    protected function prepareForValidation(): void
    {
        // Si no se proporciona slug, se generará en el servicio
        if (!$this->has('slug') && $this->has('nombre')) {
            $this->merge([
                'slug' => \Str::slug($this->nombre)
            ]);
        }

        // Establecer valor por defecto para activo
        if (!$this->has('activo')) {
            $this->merge([
                'activo' => true
            ]);
        }

        // Establecer valor por defecto para aplicar_para (todas las entidades)
        if (!$this->has('aplicar_para') || empty($this->aplicar_para)) {
            $this->merge([
                'aplicar_para' => ['proyectos', 'hitos', 'entregables']
            ]);
        }
    }
}