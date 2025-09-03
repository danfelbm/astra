<?php

namespace Modules\Asamblea\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ManageParticipantesRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta petición.
     */
    public function authorize(): bool
    {
        return $this->user()->can('asambleas.manage_participants');
    }

    /**
     * Obtener las reglas de validación que se aplican a la petición.
     */
    public function rules(): array
    {
        return match($this->method()) {
            'POST' => $this->getAssignRules(),
            'DELETE' => $this->getRemoveRules(),
            'PUT' => $this->getUpdateRules(),
            default => []
        };
    }

    /**
     * Reglas para asignar participantes (POST)
     */
    private function getAssignRules(): array
    {
        return [
            'participante_ids' => 'required|array',
            'participante_ids.*' => 'exists:users,id',
            'tipo_participacion' => 'nullable|in:asistente,moderador,secretario',
        ];
    }

    /**
     * Reglas para remover participante (DELETE)
     */
    private function getRemoveRules(): array
    {
        return [
            'participante_id' => 'required|exists:users,id',
        ];
    }

    /**
     * Reglas para actualizar participante (PUT)
     */
    private function getUpdateRules(): array
    {
        return [
            'participante_id' => 'required|exists:users,id',
            'tipo_participacion' => 'nullable|in:asistente,moderador,secretario',
            'asistio' => 'nullable|boolean',
        ];
    }

    /**
     * Obtener los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'participante_ids.required' => 'Debe seleccionar al menos un participante.',
            'participante_ids.array' => 'Los participantes deben ser un array.',
            'participante_ids.*.exists' => 'Uno de los participantes seleccionados no existe.',
            'participante_id.required' => 'El ID del participante es requerido.',
            'participante_id.exists' => 'El participante seleccionado no existe.',
            'tipo_participacion.in' => 'El tipo de participación debe ser: asistente, moderador o secretario.',
            'asistio.boolean' => 'El campo de asistencia debe ser verdadero o falso.',
        ];
    }

    /**
     * Obtener los participantes IDs para métodos que los usan
     */
    public function getParticipanteIds(): array
    {
        return $this->participante_ids ?? [];
    }

    /**
     * Obtener el ID del participante único
     */
    public function getParticipanteId(): ?int
    {
        return $this->participante_id;
    }

    /**
     * Obtener el tipo de participación con valor por defecto
     */
    public function getTipoParticipacion(): ?string
    {
        return $this->tipo_participacion;
    }

    /**
     * Obtener el estado de asistencia
     */
    public function getAsistio(): ?bool
    {
        return $this->has('asistio') ? (bool) $this->asistio : null;
    }
}