<?php

namespace App\Http\Requests\Asamblea\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAsambleaRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para realizar esta petición.
     */
    public function authorize(): bool
    {
        return $this->user()->can('asambleas.create');
    }

    /**
     * Obtener las reglas de validación que se aplican a la petición.
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tipo' => 'required|in:ordinaria,extraordinaria',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'territorio_id' => 'nullable|exists:territorios,id',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'municipio_id' => 'nullable|exists:municipios,id',
            'localidad_id' => 'nullable|exists:localidades,id',
            'lugar' => 'nullable|string|max:255',
            'quorum_minimo' => 'nullable|integer|min:1',
            'activo' => 'boolean',
            
            // Campos de Zoom
            'zoom_enabled' => 'boolean',
            'zoom_integration_type' => 'nullable|in:sdk,api,message',
            'zoom_meeting_type' => 'nullable|in:instant,scheduled,recurring',
            'zoom_settings' => 'nullable|array',
            'zoom_settings.host_video' => 'nullable|boolean',
            'zoom_settings.participant_video' => 'nullable|boolean',
            'zoom_settings.waiting_room' => 'nullable|boolean',
            'zoom_settings.mute_upon_entry' => 'nullable|boolean',
            'zoom_settings.auto_recording' => 'nullable|in:none,local,cloud',
            
            // Campos específicos para modo API
            'zoom_meeting_id' => 'nullable|string|max:255',
            'zoom_meeting_password' => 'nullable|string|max:255',
            'zoom_occurrence_ids' => 'nullable|string|max:500',
            'zoom_prefix' => 'nullable|string|max:10',
            'zoom_registration_open_date' => 'nullable|date',
            'zoom_join_url' => 'nullable|string|max:500',
            'zoom_start_url' => 'nullable|string|max:500',
            
            // Campos de mensaje estático y mensaje API
            'zoom_static_message' => 'nullable|string',
            'zoom_api_message_enabled' => 'boolean',
            'zoom_api_message' => 'nullable|string',
            
            // Campos de consulta pública de participantes
            'public_participants_enabled' => 'boolean',
            'public_participants_mode' => 'nullable|in:list,search',
            
            // Votaciones asociadas
            'votacion_ids' => 'nullable|array',
            'votacion_ids.*' => 'exists:votaciones,id',
        ];
    }

    /**
     * Obtener los mensajes de error personalizados para las reglas de validación.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es requerido.',
            'tipo.required' => 'El tipo de asamblea es requerido.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'fecha_fin.required' => 'La fecha de fin es requerida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'quorum_minimo.min' => 'El quórum mínimo debe ser al menos 1.',
        ];
    }

    /**
     * Preparar los datos para la validación.
     */
    protected function prepareForValidation(): void
    {
        // Establecer valor por defecto para zoom_integration_type si está habilitado
        if (($this->zoom_enabled ?? false) && empty($this->zoom_integration_type)) {
            $this->merge([
                'zoom_integration_type' => 'sdk'
            ]);
        }
    }

    /**
     * Obtener los datos validados con datos adicionales procesados.
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();
        $data['estado'] = 'programada'; // Estado inicial
        
        return $data;
    }
}