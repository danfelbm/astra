<?php

namespace Modules\Comentarios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Comentarios\Models\Comentario;

class UpdateComentarioRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta solicitud.
     */
    public function authorize(): bool
    {
        /** @var Comentario $comentario */
        $comentario = $this->route('comentario');

        // Verificar que puede editar (es autor y dentro de 24h)
        return $comentario->puedeSerEditadoPor($this->user());
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        $maxCaracteres = config('comentarios.max_caracteres', 10000);

        return [
            'contenido' => ['required', 'string', 'min:1', "max:{$maxCaracteres}"],
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
        ];
    }

    /**
     * Mensaje personalizado cuando la autorización falla.
     */
    protected function failedAuthorization(): void
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'No tienes permisos para editar este comentario o ha expirado el tiempo de edición (24 horas)'
        );
    }
}
