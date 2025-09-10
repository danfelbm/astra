<?php

namespace Modules\Campanas\Http\Requests\Admin;

class UpdatePlantillaEmailRequest extends StorePlantillaEmailRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta petición.
     */
    public function authorize(): bool
    {
        return $this->user()->can('campanas.plantillas.edit');
    }

    /**
     * Obtener las reglas de validación que aplican a la petición.
     */
    public function rules(): array
    {
        // Usar las mismas reglas que StorePlantillaEmailRequest
        return parent::rules();
    }
}