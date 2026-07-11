<?php

namespace Database\Factories\Sia;

use App\Models\Sia\Asistencia;
use App\Models\Sia\Persona;
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
        // `Hora` usa la fecha base 1899-12-30, como guarda el SIA real.
        return [
            'IdPersona' => Persona::factory(),
            'Fecha' => today(),
            'Hora' => '1899-12-30 '.fake()->time('H:i:s'),
            'Tipo' => Asistencia::TIPO_RELOJ,
        ];
    }
}
