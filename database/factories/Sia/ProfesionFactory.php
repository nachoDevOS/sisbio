<?php

namespace Database\Factories\Sia;

use App\Models\Sia\Profesion;
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
            'CodigoProfesion' => fake()->unique()->regexify('[0-9A-Z]{2}'),
            'NombreProfesion' => mb_strtoupper(fake()->jobTitle()),
        ];
    }
}
