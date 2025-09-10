<?php

namespace Modules\Campanas\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePlantillaEmailRequest extends FormRequest
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
            'asunto' => ['required', 'string', 'max:255'],
            'contenido_html' => ['required', 'string'],
            'contenido_texto' => ['nullable', 'string'],
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
            'asunto.required' => 'El asunto del email es requerido',
            'asunto.max' => 'El asunto no puede exceder 255 caracteres',
            'contenido_html.required' => 'El contenido HTML del email es requerido',
            'descripcion.max' => 'La descripción no puede exceder 1000 caracteres',
        ];
    }

    /**
     * Preparar los datos para validación.
     */
    protected function prepareForValidation(): void
    {
        // Establecer valor por defecto para es_activa si no se proporciona
        if (!$this->has('es_activa')) {
            $this->merge([
                'es_activa' => true,
            ]);
        }
    }

    /**
     * Obtener los datos validados y procesados.
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();
        
        // Limpiar contenido HTML de scripts maliciosos
        if (isset($data['contenido_html'])) {
            $data['contenido_html'] = $this->sanitizeHtml($data['contenido_html']);
        }
        
        return $data;
    }

    /**
     * Sanitizar contenido HTML.
     */
    private function sanitizeHtml(string $html): string
    {
        // Remover scripts y elementos peligrosos
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $html);
        
        // Mantener las variables de plantilla
        $html = str_replace(['{{', '}}'], ['__OPEN_VAR__', '__CLOSE_VAR__'], $html);
        $html = strip_tags($html, '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><table><thead><tbody><tfoot><tr><td><th><div><span><blockquote>');
        $html = str_replace(['__OPEN_VAR__', '__CLOSE_VAR__'], ['{{', '}}'], $html);
        
        return $html;
    }
}