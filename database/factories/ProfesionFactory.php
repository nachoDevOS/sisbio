<?php

namespace Database\Factories;

use App\Models\Profesion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profesion>
 */
class ProfesionFactory extends Factory
{
    protected $model = Profesion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigoProfesion' => fake()->unique()->regexify('[0-9A-Z]{2}'),
            'nombreProfesion' => mb_strtoupper(fake()->jobTitle()),
        ];
    }
}
