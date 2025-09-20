<?php

namespace Modules\Proyectos\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampoPersonalizadoRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta petición.
     */
    public function authorize(): bool
    {
        return $this->user()->can('proyectos.manage_fields');
    }

    /**
     * Obtener las reglas de validación que se aplican a la petición.
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('campos_personalizados', 'slug')
            ],
            'tipo' => 'required|in:text,number,date,textarea,select,checkbox,radio,file',
            'opciones' => 'nullable|array',
            'opciones.*.label' => 'required_with:opciones|string|max:255',
            'opciones.*.value' => 'required_with:opciones|string|max:255',
            'es_requerido' => 'boolean',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'boolean',
            'descripcion' => 'nullable|string|max:500',
            'placeholder' => 'nullable|string|max:255',
            'validacion' => 'nullable|string|max:255',
        ];
    }

    /**
     * Obtener los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del campo es requerido.',
            'nombre.max' => 'El nombre del campo no debe exceder 255 caracteres.',
            'slug.unique' => 'Ya existe un campo con este identificador.',
            'slug.alpha_dash' => 'El identificador solo puede contener letras, números, guiones y guiones bajos.',
            'tipo.required' => 'El tipo de campo es requerido.',
            'tipo.in' => 'El tipo de campo seleccionado no es válido.',
            'opciones.*.label.required_with' => 'Cada opción debe tener una etiqueta.',
            'opciones.*.value.required_with' => 'Cada opción debe tener un valor.',
            'descripcion.max' => 'La descripción no debe exceder 500 caracteres.',
            'placeholder.max' => 'El texto de ayuda no debe exceder 255 caracteres.',
        ];
    }

    /**
     * Preparar los datos para la validación.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        // Establecer valores por defecto
        if (!$this->has('activo')) {
            $data['activo'] = true;
        }

        if (!$this->has('es_requerido')) {
            $data['es_requerido'] = false;
        }

        if (!$this->has('orden')) {
            $data['orden'] = 0;
        }

        // Si el tipo no requiere opciones, limpiarlas
        if (in_array($this->tipo, ['text', 'number', 'date', 'textarea', 'file', 'checkbox'])) {
            $data['opciones'] = null;
        }

        // Generar slug si no se proporciona
        if (empty($this->slug) && !empty($this->nombre)) {
            $data['slug'] = \Illuminate\Support\Str::slug($this->nombre, '_');
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Obtener los datos validados con procesamiento adicional.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Agregar el tenant_id si aplica
        if (auth()->user()->tenant_id) {
            $validated['tenant_id'] = auth()->user()->tenant_id;
        }

        return $validated;
    }
}