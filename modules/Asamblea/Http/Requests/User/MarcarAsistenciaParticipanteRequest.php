<?php

namespace Modules\Asamblea\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class MarcarAsistenciaParticipanteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // La autorización específica (ser moderador) se verifica en el service
        // Aquí solo verificamos el permiso general
        return $this->user()->can('asambleas.participate');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'asistio' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'asistio.required' => 'El estado de asistencia es requerido.',
            'asistio.boolean' => 'El estado de asistencia debe ser verdadero o falso.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'asistio' => 'estado de asistencia',
        ];
    }

    /**
     * Get the validated asistencia value
     */
    public function getAsistio(): bool
    {
        return $this->validated()['asistio'];
    }
}