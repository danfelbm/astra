<?php

namespace Modules\Proyectos\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateObligacionContratoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return $this->user()->can('obligaciones.edit');
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        $obligacion = $this->route('obligacion');

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:obligaciones_contrato,id',
                // No puede ser hijo de sí mismo
                function ($attribute, $value, $fail) use ($obligacion) {
                    if ($value == $obligacion->id) {
                        $fail('Una obligación no puede ser su propio padre.');
                    }
                },
                // No puede ser hijo de uno de sus descendientes (evitar ciclos)
                function ($attribute, $value, $fail) use ($obligacion) {
                    if ($value) {
                        $posiblePadre = \Modules\Proyectos\Models\ObligacionContrato::find($value);
                        if ($posiblePadre) {
                            // Verificar que el posible padre no sea descendiente de la obligación actual
                            if ($posiblePadre->path && str_contains($posiblePadre->path, (string)$obligacion->id)) {
                                $fail('No se puede establecer como padre a una sub-obligación de esta obligación.');
                            }
                            // Verificar que pertenezca al mismo contrato
                            if ($posiblePadre->contrato_id != $obligacion->contrato_id) {
                                $fail('La obligación padre debe pertenecer al mismo contrato.');
                            }
                        }
                    }
                },
            ],
            'titulo' => [
                'required',
                'string',
                'max:255',
                'min:3'
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:5000'
            ],
            'orden' => [
                'nullable',
                'integer',
                'min:1'
            ],
            'archivos' => [
                'nullable',
                'array',
                'max:5' // Máximo 5 archivos nuevos
            ],
            'archivos.*' => [
                'file',
                'max:10240', // Máximo 10MB por archivo
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip'
            ],
            'archivos_eliminar' => [
                'nullable',
                'array'
            ],
            'archivos_eliminar.*' => [
                'string'
            ],
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'parent_id.exists' => 'La obligación padre seleccionada no existe',
            'titulo.required' => 'El título es obligatorio',
            'titulo.min' => 'El título debe tener al menos 3 caracteres',
            'titulo.max' => 'El título no puede exceder los 255 caracteres',
            'descripcion.max' => 'La descripción no puede exceder los 5000 caracteres',
            'archivos.max' => 'No se pueden adjuntar más de 5 archivos nuevos',
            'archivos.*.file' => 'Cada adjunto debe ser un archivo válido',
            'archivos.*.max' => 'Cada archivo no puede exceder los 10MB',
            'archivos.*.mimes' => 'Solo se permiten archivos PDF, Word, Excel, imágenes (PNG, JPG, JPEG) y ZIP',
        ];
    }

    /**
     * Prepara los datos para la validación.
     */
    protected function prepareForValidation(): void
    {
        // Limpiar espacios en blanco del título
        if ($this->has('titulo')) {
            $this->merge(['titulo' => trim($this->titulo)]);
        }

        // Limpiar espacios en blanco de la descripción
        if ($this->has('descripcion')) {
            $this->merge(['descripcion' => trim($this->descripcion)]);
        }
    }
}