<?php

namespace Modules\Campanas\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampanaRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta petición.
     */
    public function authorize(): bool
    {
        return $this->user()->can('campanas.create');
    }

    /**
     * Obtener las reglas de validación que aplican a la petición.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tipo' => ['required', Rule::in(['email', 'whatsapp', 'ambos'])],
            'estado' => ['sometimes', Rule::in(['borrador', 'programada'])],
            'segment_id' => ['required', 'exists:segments,id'],
            'plantilla_email_id' => [
                'nullable',
                'required_if:tipo,email',
                'required_if:tipo,ambos',
                'exists:plantilla_emails,id'
            ],
            'plantilla_whatsapp_id' => [
                'nullable',
                'required_if:tipo,whatsapp',
                'required_if:tipo,ambos',
                'exists:plantilla_whats_apps,id'
            ],
            'fecha_programada' => [
                'nullable',
                'required_if:estado,programada',
                'date',
                'after:now'
            ],
            // Configuración de batches y envío
            'configuracion' => ['nullable', 'array'],
            'configuracion.batch_size_email' => ['nullable', 'integer', 'min:1', 'max:100'], // Máximo 100 (límite Resend batch API)
            'configuracion.whatsapp_delay_min' => ['nullable', 'integer', 'min:5', 'max:120'], // Mínimo 5 segundos
            'configuracion.whatsapp_delay_max' => ['nullable', 'integer', 'min:5', 'max:120'], // Máximo 120 segundos
            'configuracion.enable_tracking' => ['nullable', 'boolean'],
            'configuracion.enable_pixel_tracking' => ['nullable', 'boolean'],
            'configuracion.enable_click_tracking' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Obtener los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la campaña es requerido',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'tipo.required' => 'El tipo de campaña es requerido',
            'tipo.in' => 'El tipo de campaña debe ser: email, whatsapp o ambos',
            'segment_id.required' => 'Debe seleccionar un segmento de destinatarios',
            'segment_id.exists' => 'El segmento seleccionado no existe',
            'plantilla_email_id.required_if' => 'Debe seleccionar una plantilla de email para campañas de tipo email',
            'plantilla_email_id.exists' => 'La plantilla de email seleccionada no existe',
            'plantilla_whatsapp_id.required_if' => 'Debe seleccionar una plantilla de WhatsApp para campañas de tipo WhatsApp',
            'plantilla_whatsapp_id.exists' => 'La plantilla de WhatsApp seleccionada no existe',
            'fecha_programada.required_if' => 'Debe especificar una fecha para campañas programadas',
            'fecha_programada.date' => 'La fecha programada debe ser una fecha válida',
            'fecha_programada.after' => 'La fecha programada debe ser posterior a la fecha actual',
            'configuracion.batch_size_email.min' => 'El tamaño del lote de emails debe ser al menos 1',
            'configuracion.batch_size_email.max' => 'El tamaño del lote de emails no puede exceder 100 (límite de Resend)',
            'configuracion.whatsapp_delay_min.min' => 'El intervalo mínimo debe ser al menos 5 segundos',
            'configuracion.whatsapp_delay_min.max' => 'El intervalo mínimo no puede exceder 120 segundos',
            'configuracion.whatsapp_delay_max.min' => 'El intervalo máximo debe ser al menos 5 segundos',
            'configuracion.whatsapp_delay_max.max' => 'El intervalo máximo no puede exceder 120 segundos',
        ];
    }

    /**
     * Preparar los datos para validación.
     */
    protected function prepareForValidation(): void
    {
        // Establecer estado por defecto
        if (!$this->has('estado')) {
            $this->merge([
                'estado' => 'borrador',
            ]);
        }
        
        // Establecer tracking_enabled por defecto
        if (!$this->has('tracking_enabled')) {
            $this->merge([
                'tracking_enabled' => true,
            ]);
        }
    }

    /**
     * Validaciones adicionales después de las reglas básicas.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar que el intervalo mínimo sea menor o igual al máximo
            $config = $this->input('configuracion', []);
            $minDelay = $config['whatsapp_delay_min'] ?? null;
            $maxDelay = $config['whatsapp_delay_max'] ?? null;

            if ($minDelay !== null && $maxDelay !== null && $minDelay > $maxDelay) {
                $validator->errors()->add(
                    'configuracion.whatsapp_delay_min',
                    'El intervalo mínimo debe ser menor o igual al intervalo máximo'
                );
            }
            
            // Validar que haya destinatarios en el segmento
            if ($this->filled('segment_id')) {
                $segment = \Modules\Core\Models\Segment::find($this->segment_id);
                
                if ($segment) {
                    $count = $segment->getCount();
                    
                    if ($count === 0) {
                        $validator->errors()->add(
                            'segment_id',
                            'El segmento seleccionado no tiene destinatarios'
                        );
                    }
                }
            }
        });
    }

    /**
     * Obtener los datos validados y procesados.
     */
    public function getValidatedData(): array
    {
        return $this->validated();
    }
}