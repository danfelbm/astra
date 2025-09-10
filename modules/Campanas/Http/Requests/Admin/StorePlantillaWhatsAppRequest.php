<?php

namespace Modules\Campanas\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePlantillaWhatsAppRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta petición.
     */
    public function authorize(): bool
    {
        return $this->user()->can('campanas.plantillas.create');
    }

    /**
     * Obtener las reglas de validación que aplican a la petición.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'contenido' => ['required', 'string', 'max:4096'],
            'usa_formato' => ['boolean'],
            'es_activa' => ['boolean'],
        ];
    }

    /**
     * Obtener los mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la plantilla es requerido',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'contenido.required' => 'El contenido del mensaje es requerido',
            'contenido.max' => 'El mensaje no puede exceder 4096 caracteres (límite de WhatsApp)',
            'descripcion.max' => 'La descripción no puede exceder 1000 caracteres',
        ];
    }

    /**
     * Preparar los datos para validación.
     */
    protected function prepareForValidation(): void
    {
        // Convertir valores booleanos correctamente
        $this->merge([
            'es_activa' => $this->boolean('es_activa'),
            'usa_formato' => $this->boolean('usa_formato'),
        ]);
    }

    /**
     * Obtener los datos validados y procesados.
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();
        
        // Validar longitud del mensaje con variables
        if (isset($data['contenido'])) {
            $longitudEstimada = $this->estimarLongitudConVariables($data['contenido']);
            
            if ($longitudEstimada > 4096) {
                throw new \Illuminate\Validation\ValidationException(
                    $this->getValidatorInstance()
                        ->errors()
                        ->add('contenido', "El mensaje con variables podría exceder el límite de 4096 caracteres (estimado: {$longitudEstimada})")
                );
            }
        }
        
        return $data;
    }

    /**
     * Estimar longitud del mensaje con variables reemplazadas.
     */
    private function estimarLongitudConVariables(string $contenido): int
    {
        // Reemplazar variables con valores promedio estimados
        $estimaciones = [
            '{{nombre}}' => 'Juan Carlos Pérez González', // 25 caracteres promedio
            '{{email}}' => 'usuario.ejemplo@dominio.com', // 28 caracteres promedio
            '{{telefono}}' => '+57 300 123 4567', // 16 caracteres
            '{{documento_identidad}}' => '1234567890', // 10 caracteres
            '{{territorio}}' => 'Territorio Central', // 18 caracteres
            '{{departamento}}' => 'Cundinamarca', // 12 caracteres
            '{{municipio}}' => 'Bogotá D.C.', // 11 caracteres
            '{{localidad}}' => 'Chapinero', // 9 caracteres
            '{{fecha_actual}}' => '31/12/2024', // 10 caracteres
            '{{ano_actual}}' => '2024', // 4 caracteres
        ];
        
        foreach ($estimaciones as $variable => $valorEstimado) {
            $contenido = str_replace($variable, $valorEstimado, $contenido);
        }
        
        return strlen($contenido);
    }
}