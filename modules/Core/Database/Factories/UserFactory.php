<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Core\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = \Modules\Core\Models\User::class;

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
        $tenantId = app(\Modules\Core\Services\TenantService::class)->getCurrentTenant()?->id ?? 1;

        // Seleccionar ubicación geográfica respetando jerarquía
        $geografico = $this->getRandomGeographicLocation();

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
            'territorio_id' => $geografico['territorio_id'],
            'departamento_id' => $geografico['departamento_id'],
            'municipio_id' => $geografico['municipio_id'],
            'localidad_id' => $geografico['localidad_id'],
            'activo' => true,
            'es_miembro' => fake()->boolean(70), // 70% probabilidad de ser miembro
        ];
    }

    /**
     * Configure the model factory.
     * Asignar rol 'user' automáticamente después de crear
     */
    public function configure(): static
    {
        return $this->afterCreating(function (\Modules\Core\Models\User $user) {
            // Asignar rol 'user' por defecto a todos los usuarios creados con factory
            if (!$user->hasAnyRole()) {
                $user->assignRole('user');
            }
        });
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
     * Obtener ubicación geográfica aleatoria respetando jerarquía
     */
    private function getRandomGeographicLocation(): array
    {
        // Seleccionar un departamento aleatorio
        $departamento = \DB::table('departamentos')
            ->where('territorio_id', 1) // Colombia
            ->inRandomOrder()
            ->first();

        if (!$departamento) {
            // Si no hay departamentos, usar valores por defecto
            return [
                'territorio_id' => 1,
                'departamento_id' => null,
                'municipio_id' => null,
                'localidad_id' => null,
            ];
        }

        // Seleccionar un municipio aleatorio DE ESE departamento
        $municipio = \DB::table('municipios')
            ->where('departamento_id', $departamento->id)
            ->inRandomOrder()
            ->first();

        if (!$municipio) {
            return [
                'territorio_id' => 1,
                'departamento_id' => $departamento->id,
                'municipio_id' => null,
                'localidad_id' => null,
            ];
        }

        // 30% de probabilidad de asignar una localidad
        $localidad = null;
        if (fake()->boolean(30)) {
            $localidadData = \DB::table('localidades')
                ->where('municipio_id', $municipio->id)
                ->inRandomOrder()
                ->first();
            $localidad = $localidadData?->id;
        }

        return [
            'territorio_id' => 1, // Colombia
            'departamento_id' => $departamento->id,
            'municipio_id' => $municipio->id,
            'localidad_id' => $localidad,
        ];
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
