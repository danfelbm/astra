<?php

namespace Modules\Votaciones\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVotacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('votaciones.edit');
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'estado' => ['required', Rule::in(['borrador', 'activa', 'finalizada'])],
            'resultados_publicos' => 'boolean',
            'allow_tokens_download' => 'boolean',
            'fecha_publicacion_resultados' => 'nullable|date',
            'limite_censo' => 'nullable|date',
            'mensaje_limite_censo' => 'nullable|string|max:1000',
            'formulario_config' => 'required|array|min:1',
            'timezone' => 'required|string|timezone',
            'territorios_ids' => 'nullable|array',
            'territorios_ids.*' => 'exists:territorios,id',
            'departamentos_ids' => 'nullable|array',
            'departamentos_ids.*' => 'exists:departamentos,id',
            'municipios_ids' => 'nullable|array',
            'municipios_ids.*' => 'exists:municipios,id',
            'localidades_ids' => 'nullable|array',
            'localidades_ids.*' => 'exists:localidades,id',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título es requerido.',
            'categoria_id.required' => 'La categoría es requerida.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'fecha_fin.required' => 'La fecha de fin es requerida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'formulario_config.required' => 'Debe configurar al menos un campo en el formulario.',
            'formulario_config.min' => 'Debe tener al menos un campo en el formulario.',
            'timezone.required' => 'La zona horaria es requerida.',
            'timezone.timezone' => 'La zona horaria seleccionada no es válida.',
        ];
    }
}
