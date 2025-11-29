<?php

namespace Modules\Comentarios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComentarioRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta solicitud.
     */
    public function authorize(): bool
    {
        return $this->user()->can('comentarios.create');
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        $maxCaracteres = config('comentarios.max_caracteres', 10000);

        return [
            'contenido' => ['required', 'string', 'min:1', "max:{$maxCaracteres}"],
            'parent_id' => ['nullable', 'integer', 'exists:comentarios,id'],
            'quoted_comentario_id' => ['nullable', 'integer', 'exists:comentarios,id'],
        ];
    }

    /**
     * Mensajes de validación personalizados.
     */
    public function messages(): array
    {
        return [
            'contenido.required' => 'El contenido del comentario es requerido',
            'contenido.min' => 'El comentario no puede estar vacío',
            'contenido.max' => 'El comentario excede el límite de caracteres permitido',
            'parent_id.exists' => 'El comentario padre no existe',
            'quoted_comentario_id.exists' => 'El comentario citado no existe',
        ];
    }
}
