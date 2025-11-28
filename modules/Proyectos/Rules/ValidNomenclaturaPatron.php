<?php

namespace Modules\Proyectos\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Proyectos\Services\NomenclaturaService;

/**
 * Regla de validación para patrones de nomenclatura de archivos.
 * Verifica que los tokens usados en el patrón sean válidos.
 */
class ValidNomenclaturaPatron implements ValidationRule
{
    /**
     * Ejecuta la validación.
     *
     * @param string $attribute Nombre del atributo
     * @param mixed $value Valor a validar
     * @param Closure $fail Callback para reportar fallo
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Si es null o vacío, es válido (se usará el default)
        if (empty($value)) {
            return;
        }

        $nomenclaturaService = app(NomenclaturaService::class);

        if (!$nomenclaturaService->validarPatron($value)) {
            $fail('El patrón de nomenclatura contiene tokens inválidos. Tokens permitidos: {proyecto}, {proyecto_id}, {hito}, {hito_id}, {entregable}, {entregable_id}, {fecha}, {fecha:formato}, {original}');
        }
    }
}
