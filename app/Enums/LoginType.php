<?php

namespace App\Enums;

enum LoginType: string
{
    case EMAIL = 'email';
    case DOCUMENTO = 'documento';

    /**
     * Obtener etiqueta legible para el tipo de login
     */
    public function label(): string
    {
        return match($this) {
            self::EMAIL => 'Correo Electrónico',
            self::DOCUMENTO => 'Documento de Identidad',
        };
    }

    /**
     * Obtener tipo de input HTML correspondiente
     */
    public function inputType(): string
    {
        return match($this) {
            self::EMAIL => 'email',
            self::DOCUMENTO => 'text',
        };
    }

    /**
     * Obtener placeholder para el input
     */
    public function placeholder(): string
    {
        return match($this) {
            self::EMAIL => 'correo@ejemplo.com',
            self::DOCUMENTO => '12345678',
        };
    }

    /**
     * Obtener patrón de validación HTML
     */
    public function pattern(): ?string
    {
        return match($this) {
            self::EMAIL => null,
            self::DOCUMENTO => '[0-9]*',
        };
    }

    /**
     * Obtener reglas de validación Laravel
     */
    public function validationRules(): array
    {
        return match($this) {
            self::EMAIL => ['required', 'email'],
            self::DOCUMENTO => ['required', 'string', 'regex:/^[0-9]+$/'],
        };
    }

    /**
     * Obtener todos los valores como array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Obtener opciones para select/dropdown
     */
    public static function options(): array
    {
        return array_map(
            fn($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}