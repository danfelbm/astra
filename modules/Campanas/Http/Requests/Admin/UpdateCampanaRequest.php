<?php

namespace Modules\Campanas\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateCampanaRequest extends StoreCampanaRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta petición.
     */
    public function authorize(): bool
    {
        return $this->user()->can('campanas.edit');
    }

    /**
     * Obtener las reglas de validación que aplican a la petición.
     */
    public function rules(): array
    {
        $rules = parent::rules();
        
        // Modificar regla de estado para campañas existentes
        $campana = $this->route('campana');
        
        if ($campana) {
            // Permitir más estados según el estado actual
            $estadosPermitidos = ['borrador', 'programada'];
            
            if ($campana->estado === 'pausada') {
                $estadosPermitidos[] = 'pausada';
            }
            
            $rules['estado'] = ['sometimes', Rule::in($estadosPermitidos)];
            
            // Si la campaña ya está en proceso, no requerir plantillas
            if (!in_array($campana->estado, ['borrador', 'programada'])) {
                $rules['plantilla_email_id'] = ['nullable', 'exists:plantilla_emails,id'];
                $rules['plantilla_whatsapp_id'] = ['nullable', 'exists:plantilla_whats_apps,id'];
            }
        }
        
        return $rules;
    }

    /**
     * Validaciones adicionales después de las reglas básicas.
     */
    public function withValidator($validator): void
    {
        parent::withValidator($validator);
        
        $validator->after(function ($validator) {
            $campana = $this->route('campana');
            
            if ($campana) {
                // No permitir cambiar tipo si ya tiene envíos
                if ($this->filled('tipo') && $campana->tipo !== $this->tipo) {
                    if ($campana->envios()->exists()) {
                        $validator->errors()->add(
                            'tipo',
                            'No se puede cambiar el tipo de campaña después de iniciar envíos'
                        );
                    }
                }
                
                // No permitir cambiar segmento si ya tiene envíos
                if ($this->filled('segment_id') && $campana->segment_id != $this->segment_id) {
                    if ($campana->envios()->exists()) {
                        $validator->errors()->add(
                            'segment_id',
                            'No se puede cambiar el segmento después de iniciar envíos'
                        );
                    }
                }

                // No permitir cambiar modo de audiencia si ya tiene envíos
                if ($this->filled('audience_mode') && $campana->audience_mode !== $this->audience_mode) {
                    if ($campana->envios()->exists()) {
                        $validator->errors()->add(
                            'audience_mode',
                            'No se puede cambiar el modo de audiencia después de iniciar envíos'
                        );
                    }
                }

                // No permitir cambiar filtros si ya tiene envíos
                if ($this->filled('filters') && $campana->filters != $this->filters) {
                    if ($campana->envios()->exists()) {
                        $validator->errors()->add(
                            'filters',
                            'No se pueden cambiar los filtros después de iniciar envíos'
                        );
                    }
                }
                
                // Validar cambios de estado
                if ($this->filled('estado')) {
                    $estadoActual = $campana->estado;
                    $nuevoEstado = $this->estado;
                    
                    // No permitir retroceder de ciertos estados
                    $prohibidos = [
                        'completada' => ['borrador', 'programada', 'enviando', 'pausada'],
                        'cancelada' => ['borrador', 'programada', 'enviando', 'pausada', 'completada'],
                        'enviando' => ['borrador'],
                    ];
                    
                    if (isset($prohibidos[$estadoActual]) && in_array($nuevoEstado, $prohibidos[$estadoActual])) {
                        $validator->errors()->add(
                            'estado',
                            "No se puede cambiar de estado '{$estadoActual}' a '{$nuevoEstado}'"
                        );
                    }
                }
            }
        });
    }
}