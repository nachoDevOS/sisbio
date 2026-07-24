<?php

namespace Database\Factories;

use App\Models\Asistencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asistencia>
 */
class AsistenciaFactory extends Factory
{
    protected $model = Asistencia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // `hora` usa la fecha base 1899-12-30, como el SIA real.
        return [
            'ci' => (string) fake()->unique()->numberBetween(1, 9999999),
            'fecha' => today(),
            'hora' => '1899-12-30 '.fake()->time('H:i:s'),
            'tipo' => Asistencia::TIPO_RELOJ,
        ];
    }
}
