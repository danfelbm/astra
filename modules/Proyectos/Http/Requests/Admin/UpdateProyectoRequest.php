<?php

namespace Modules\Proyectos\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Proyectos\Models\CampoPersonalizado;
use Modules\Proyectos\Rules\ValidNomenclaturaPatron;

class UpdateProyectoRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta petición.
     */
    public function authorize(): bool
    {
        return $this->user()->can('proyectos.edit');
    }

    /**
     * Obtener las reglas de validación que se aplican a la petición.
     */
    public function rules(): array
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after:fecha_inicio',
            'estado' => 'required|in:planificacion,en_progreso,pausado,completado,cancelado',
            'prioridad' => 'required|in:baja,media,alta,critica',
            'responsable_id' => 'nullable|exists:users,id',
            'activo' => 'boolean',
            'campos_personalizados' => 'nullable|array',
            'etiquetas' => 'nullable|array|max:' . config('proyectos.etiquetas.max_por_proyecto', 10),
            'etiquetas.*' => 'exists:etiquetas,id',
            'gestores' => 'nullable|array',
            'gestores.*' => 'exists:users,id',
            'nomenclatura_archivos' => ['nullable', 'string', 'max:255', new ValidNomenclaturaPatron],
        ];

        // Agregar reglas dinámicas para campos personalizados
        $camposPersonalizados = CampoPersonalizado::activos()->get();

        foreach ($camposPersonalizados as $campo) {
            if ($campo->es_requerido || $this->has("campos_personalizados.{$campo->slug}")) {
                $rules["campos_personalizados.{$campo->slug}"] = $campo->reglas_validacion;
            }
        }

        return $rules;
    }

    /**
     * Obtener los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proyecto es requerido.',
            'nombre.max' => 'El nombre del proyecto no debe exceder 255 caracteres.',
            'descripcion.max' => 'La descripción no debe exceder 5000 caracteres.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'estado.required' => 'El estado del proyecto es requerido.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'prioridad.required' => 'La prioridad del proyecto es requerida.',
            'prioridad.in' => 'La prioridad seleccionada no es válida.',
            'responsable_id.exists' => 'El responsable seleccionado no existe.',
            'etiquetas.array' => 'Las etiquetas deben ser un arreglo válido.',
            'etiquetas.*.exists' => 'Una o más etiquetas seleccionadas no son válidas.',
            'etiquetas.max' => 'No puedes asignar más de ' . config('proyectos.etiquetas.max_por_proyecto', 10) . ' etiquetas a un proyecto.',
        ];
    }

    /**
     * Preparar los datos para la validación.
     */
    protected function prepareForValidation(): void
    {
        // Establecer el usuario que actualiza
        $this->merge([
            'updated_by' => auth()->id(),
        ]);
    }
}