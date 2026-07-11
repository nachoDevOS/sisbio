<?php

namespace Database\Factories\Sia;

use App\Models\Sia\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Persona>
 */
class PersonaFactory extends Factory
{
    protected $model = Persona::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // IdPersona es char(12) en SQL Server: se imita el relleno con espacios.
        return [
            'IdPersona' => str_pad((string) fake()->unique()->numberBetween(1, 999999), 12),
            'Paterno' => fake()->lastName(),
            'Materno' => fake()->lastName(),
            'Nombres' => fake()->firstName(),
            'FechaNacimiento' => fake()->dateTimeBetween('-60 years', '-20 years'),
            'Sexo' => fake()->randomElement(['M', 'F']),
            'EstadoCivil' => 'S',
            'CodigoProfesion' => '01',
            'MarcaDirecta' => false,
            'PinReloj' => (string) fake()->unique()->numberBetween(8001, 8500),
        ];
    }
}
