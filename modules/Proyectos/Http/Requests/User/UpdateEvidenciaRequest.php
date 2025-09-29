<?php

namespace Modules\Proyectos\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Proyectos\Models\Evidencia;

class UpdateEvidenciaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta petición.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $evidencia = $this->route('evidencia');

        // Solo puede editar si es el propietario y está pendiente
        // o si tiene permisos de editar cualquier evidencia
        return $evidencia->puedeSerEditadaPor($this->user());
    }

    /**
     * Obtiene las reglas de validación que aplican a la petición.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $evidencia = $this->route('evidencia');
        $tipoEvidencia = $this->input('tipo_evidencia', $evidencia->tipo_evidencia);
        $maxFileSize = $this->getMaxFileSizeByType($tipoEvidencia);
        $mimeTypes = $this->getMimeTypesByType($tipoEvidencia);

        return [
            'tipo_evidencia' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['imagen', 'video', 'audio', 'documento'])
            ],

            'archivo_path' => [
                'sometimes',
                'string'
            ],

            'archivo_nombre' => [
                'nullable',
                'string',
                'max:255'
            ],

            'archivos_paths' => [
                'nullable',
                'array',
                'max:10' // Máximo 10 archivos
            ],

            'archivos_paths.*' => [
                'required',
                'string'
            ],

            'archivos_nombres' => [
                'nullable',
                'array'
            ],

            'archivos_nombres.*' => [
                'nullable',
                'string',
                'max:255'
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:1000'
            ],

            'entregable_ids' => [
                'nullable',
                'array'
            ],

            'entregable_ids.*' => [
                'integer',
                'exists:entregables,id',
                function ($attribute, $value, $fail) {
                    // Verificar que los entregables pertenecen al proyecto del contrato
                    $contrato = $this->route('contrato');
                    if ($contrato && $contrato->proyecto) {
                        $entregableIds = $contrato->proyecto->hitos()
                            ->with('entregables')
                            ->get()
                            ->pluck('entregables')
                            ->flatten()
                            ->pluck('id')
                            ->toArray();

                        if (!in_array($value, $entregableIds)) {
                            $fail('El entregable seleccionado no pertenece al proyecto asociado.');
                        }
                    }
                }
            ],

            'metadata' => [
                'nullable',
                'array'
            ],

            'metadata.size' => [
                'nullable',
                'integer',
                'min:1',
                "max:{$maxFileSize}"
            ],

            'metadata.mime_type' => [
                'nullable',
                'string',
                Rule::in($mimeTypes)
            ],

            'metadata.duration' => [
                'nullable',
                'numeric',
                'min:0'
            ]
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_evidencia.required' => 'Debe seleccionar el tipo de evidencia.',
            'tipo_evidencia.in' => 'El tipo de evidencia seleccionado no es válido.',

            'archivos_paths.max' => 'No puede subir más de 10 archivos por evidencia.',
            'archivos_paths.*.required' => 'Cada archivo debe tener una ruta válida.',
            'archivos_nombres.*.max' => 'El nombre del archivo no puede tener más de 255 caracteres.',

            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',

            'entregables.array' => 'Los entregables deben ser una lista válida.',
            'entregables.*.exists' => 'Uno o más entregables seleccionados no existen.',

            'metadata.size.max' => 'El archivo excede el tamaño máximo permitido.',
            'metadata.mime_type.in' => 'El tipo de archivo no está permitido para este tipo de evidencia.'
        ];
    }

    /**
     * Obtiene el tamaño máximo de archivo por tipo de evidencia.
     *
     * @param string|null $tipo
     * @return int Tamaño en bytes
     */
    private function getMaxFileSizeByType(?string $tipo): int
    {
        $sizes = [
            'imagen' => 10 * 1024 * 1024,     // 10 MB
            'video' => 500 * 1024 * 1024,     // 500 MB
            'audio' => 50 * 1024 * 1024,      // 50 MB
            'documento' => 20 * 1024 * 1024   // 20 MB
        ];

        return $sizes[$tipo] ?? 10 * 1024 * 1024; // Default 10 MB
    }

    /**
     * Obtiene los tipos MIME permitidos por tipo de evidencia.
     *
     * @param string|null $tipo
     * @return array
     */
    private function getMimeTypesByType(?string $tipo): array
    {
        if (!$tipo) {
            return [];
        }

        return Evidencia::getMimeTypesPermitidos($tipo);
    }

    /**
     * Prepara los datos para validación.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Si viene de un formulario con estructura anidada
        if ($this->has('formulario_data')) {
            $this->merge($this->input('formulario_data'));
        }
    }
}