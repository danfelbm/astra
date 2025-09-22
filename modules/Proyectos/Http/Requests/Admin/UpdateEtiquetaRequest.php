<?php

namespace Modules\Proyectos\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEtiquetaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return $this->user()->can('etiquetas.edit');
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        $tenant_id = auth()->user()->tenant_id ?? null;
        $etiqueta_id = $this->route('etiqueta')->id ?? $this->route('etiqueta');

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
                Rule::unique('etiquetas')->where(function ($query) use ($tenant_id) {
                    return $query->where('tenant_id', $tenant_id);
                })->ignore($etiqueta_id),
            ],
            'categoria_etiqueta_id' => [
                'required',
                'integer',
                'exists:categorias_etiquetas,id',
            ],
            'color' => [
                'nullable',
                'string',
                Rule::in([
                    'gray', 'red', 'orange', 'amber', 'yellow', 'lime', 'green',
                    'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet',
                    'purple', 'fuchsia', 'pink', 'rose'
                ]),
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:500',
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:etiquetas,id',
                function ($attribute, $value, $fail) use ($etiqueta_id) {
                    // No puede ser su propio padre
                    if ($value && $value == $etiqueta_id) {
                        $fail('Una etiqueta no puede ser su propio padre.');
                    }
                },
            ],
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la etiqueta es obligatorio',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres',
            'nombre.max' => 'El nombre no puede exceder los 100 caracteres',
            'slug.unique' => 'Ya existe una etiqueta con este slug',
            'categoria_etiqueta_id.required' => 'Debe seleccionar una categoría para la etiqueta',
            'categoria_etiqueta_id.exists' => 'La categoría seleccionada no existe',
            'color.in' => 'El color seleccionado no es válido',
            'descripcion.max' => 'La descripción no puede exceder los 500 caracteres',
            'parent_id.exists' => 'La etiqueta padre seleccionada no existe',
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
    }
}