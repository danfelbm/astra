<?php

namespace Modules\Proyectos\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntregableRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return $this->user()->can('entregables.create');
    }

    /**
     * Obtiene las reglas de validación que aplican a la solicitud.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'fecha_inicio' => ['nullable', 'date', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'estado' => ['nullable', Rule::in(['pendiente', 'en_progreso', 'completado', 'cancelado'])],
            'prioridad' => ['nullable', Rule::in(['baja', 'media', 'alta'])],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'usuarios' => ['nullable', 'array'],
            'usuarios.*.user_id' => ['required_with:usuarios', 'exists:users,id'],
            'usuarios.*.rol' => ['required_with:usuarios', Rule::in(['responsable', 'colaborador', 'revisor'])],
            'campos_personalizados' => ['nullable', 'array'],
            'etiquetas' => ['nullable', 'array', 'max:10'],
            'etiquetas.*' => ['exists:etiquetas,id'],
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del entregable es obligatorio',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres',
            'descripcion.max' => 'La descripción no puede exceder los 1000 caracteres',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio',
            'orden.integer' => 'El orden debe ser un número entero',
            'orden.min' => 'El orden debe ser un número positivo',
            'estado.in' => 'El estado seleccionado no es válido',
            'prioridad.in' => 'La prioridad seleccionada no es válida',
            'responsable_id.exists' => 'El responsable seleccionado no existe',
            'usuarios.array' => 'Los usuarios asignados deben ser un arreglo',
            'usuarios.*.user_id.required_with' => 'El ID del usuario es obligatorio',
            'usuarios.*.user_id.exists' => 'El usuario seleccionado no existe',
            'usuarios.*.rol.required_with' => 'El rol del usuario es obligatorio',
            'usuarios.*.rol.in' => 'El rol seleccionado no es válido',
        ];
    }

    /**
     * Prepara los datos para validación.
     */
    protected function prepareForValidation(): void
    {
        // Si no se proporciona un estado, establecer 'pendiente' por defecto
        if (!$this->has('estado')) {
            $this->merge(['estado' => 'pendiente']);
        }

        // Si no se proporciona prioridad, establecer 'media' por defecto
        if (!$this->has('prioridad')) {
            $this->merge(['prioridad' => 'media']);
        }

        // Si no se proporciona orden, será calculado en el servicio
        if (!$this->has('orden')) {
            $this->merge(['orden' => null]);
        }
    }
}