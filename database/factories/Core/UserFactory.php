<?php

namespace Database\Factories\Core;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Core\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = \App\Models\Core\User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Obtener tenant_id del contexto actual o usar 1 por defecto
        $tenantId = app(\App\Services\Core\TenantService::class)->getCurrentTenant()?->id ?? 1;
        
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->userName() . '@example.com', // Forzar @example.com
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'telefono' => $this->generateFakePhoneNumber(),
            'documento_identidad' => fake()->unique()->numerify('##########'), // 10 dígitos
            'tipo_documento' => fake()->randomElement(['CC', 'CE', 'TI']),
            'tenant_id' => $tenantId,
            'territorio_id' => 1, // Colombia por defecto
            'departamento_id' => fake()->numberBetween(1, 33), // IDs de departamentos existentes
            'activo' => true,
            'es_miembro' => fake()->boolean(70), // 70% probabilidad de ser miembro
        ];
    }

    /**
     * Generar número de teléfono colombiano falso
     */
    private function generateFakePhoneNumber(): string
    {
        // Números colombianos empiezan con 3 y tienen 10 dígitos
        return '3' . fake()->numerify('#########');
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
