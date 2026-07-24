<?php

namespace Database\Factories;

use App\Models\Persona;
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
        return [
            'ci' => (string) fake()->unique()->numberBetween(1, 9999999),
            'paterno' => fake()->lastName(),
            'materno' => fake()->lastName(),
            'nombres' => fake()->firstName(),
            'fechaNacimiento' => fake()->dateTimeBetween('-60 years', '-20 years'),
            'sexo' => fake()->randomElement(['M', 'F']),
            'estadoCivil' => 'S',
            'codigoProfesion' => '01',
            'marcaDirecta' => false,
            'pinReloj' => (string) fake()->unique()->numberBetween(8001, 8500),
        ];
    }
}
