<?php

namespace Modules\Proyectos\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Proyectos\Models\CampoPersonalizado;

class UpdateContratoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return $this->user()->can('contratos.edit');
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        $contratoId = $this->route('contrato')->id;

        $reglas = [
            // Campos básicos
            'proyecto_id' => 'required|exists:proyectos,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',

            // Estado y tipo
            'estado' => 'required|in:borrador,activo,finalizado,cancelado',
            'tipo' => 'required|in:servicio,obra,suministro,consultoria,otro',

            // Información financiera
            'monto_total' => 'nullable|numeric|min:0|max:999999999999.99',
            'moneda' => 'required|string|size:3',

            // Responsable
            'responsable_id' => 'nullable|exists:users,id',

            // Usuario contraparte del sistema
            'contraparte_user_id' => 'nullable|exists:users,id',

            // Participantes del contrato
            'participantes' => 'nullable|array',
            'participantes.*.user_id' => 'required_with:participantes|exists:users,id',
            'participantes.*.rol' => 'required_with:participantes|in:participante,observador,aprobador',
            'participantes.*.notas' => 'nullable|string|max:500',

            // Información de contraparte externa (solo si no hay usuario contraparte)
            'contraparte_nombre' => 'nullable|required_without:contraparte_user_id|string|max:255',
            'contraparte_identificacion' => 'nullable|string|max:50',
            'contraparte_email' => 'nullable|email|max:255',
            'contraparte_telefono' => 'nullable|string|max:50',

            // Archivos y observaciones
            'archivo_pdf' => 'nullable|file|mimes:pdf|max:10240', // Max 10MB (retrocompatibilidad)

            // Múltiples archivos
            'archivos_paths' => 'nullable|array|max:10',
            'archivos_paths.*' => 'nullable|string',
            'archivos_nombres' => 'nullable|array',
            'archivos_nombres.*' => 'nullable|string|max:255',
            'tipos_archivos' => 'nullable|array',

            'observaciones' => 'nullable|string|max:5000',
        ];

        // Agregar validación para campos personalizados
        $camposPersonalizados = CampoPersonalizado::paraContratos()->activos()->get();

        foreach ($camposPersonalizados as $campo) {
            $campoKey = 'campos_personalizados.' . $campo->id;
            $reglas[$campoKey] = $campo->reglas_validacion;
        }

        return $reglas;
    }

    /**
     * Obtiene los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'proyecto_id.required' => 'El proyecto es obligatorio',
            'proyecto_id.exists' => 'El proyecto seleccionado no existe',
            'nombre.required' => 'El nombre del contrato es obligatorio',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio',
            'tipo.required' => 'El tipo de contrato es obligatorio',
            'tipo.in' => 'El tipo de contrato no es válido',
            'estado.required' => 'El estado es obligatorio',
            'estado.in' => 'El estado no es válido',
            'monto_total.numeric' => 'El monto debe ser un número',
            'monto_total.min' => 'El monto no puede ser negativo',
            'monto_total.max' => 'El monto excede el límite permitido',
            'moneda.required' => 'La moneda es obligatoria',
            'moneda.size' => 'El código de moneda debe tener exactamente 3 caracteres',
            'responsable_id.exists' => 'El responsable seleccionado no existe',
            'contraparte_user_id.exists' => 'El usuario contraparte seleccionado no existe',
            'participantes.*.user_id.exists' => 'Uno de los participantes seleccionados no existe',
            'participantes.*.rol.in' => 'El rol del participante no es válido',
            'contraparte_nombre.required_without' => 'El nombre de la contraparte es obligatorio si no se selecciona un usuario del sistema',
            'contraparte_email.email' => 'El email de la contraparte no es válido',
            'archivo_pdf.mimes' => 'El archivo debe ser un PDF',
            'archivo_pdf.max' => 'El archivo no puede exceder 10MB',
            'archivos_paths.max' => 'No se pueden subir más de 10 archivos',
            'archivos_nombres.*.max' => 'El nombre del archivo no puede exceder 255 caracteres',
        ];
    }

    /**
     * Prepara los datos para la validación.
     */
    protected function prepareForValidation(): void
    {
        // Decodificar arrays de archivos si vienen como JSON strings desde FormData
        $data = [];

        if ($this->has('archivos_paths') && is_string($this->input('archivos_paths'))) {
            $data['archivos_paths'] = json_decode($this->input('archivos_paths'), true) ?: [];
        }

        if ($this->has('archivos_nombres') && is_string($this->input('archivos_nombres'))) {
            $data['archivos_nombres'] = json_decode($this->input('archivos_nombres'), true) ?: [];
        }

        if ($this->has('tipos_archivos') && is_string($this->input('tipos_archivos'))) {
            $data['tipos_archivos'] = json_decode($this->input('tipos_archivos'), true) ?: [];
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }
}