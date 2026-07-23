<?php

namespace Database\Factories\Sia;

use App\Models\Sia\DiaTurno;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<DiaTurno>
 */
class DiaTurnoFactory extends Factory
{
    protected $model = DiaTurno::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hora = fn (string $hm): Carbon => Carbon::createFromFormat('Y-m-d H:i', "1899-12-30 {$hm}");

        return [
            'IdTurno' => fake()->unique()->regexify('[A-Z0-9]{3}'),
            'Dia' => (string) fake()->numberBetween(1, 7),
            'NombreTurno' => 'Turno '.fake()->numberBetween(1, 99),
            'HEntrada' => $hora('08:00'),
            'HTolerancia' => $hora('08:10'),
            'EMinima' => $hora('07:00'),
            'EMaxima' => $hora('10:00'),
            'HSalida' => $hora('16:00'),
            'STolerancia' => $hora('16:00'),
            'SMinima' => $hora('16:00'),
            'SMaxima' => $hora('23:59'),
            'HTrabajadas' => 8,
            'SiguienteDia' => false,
        ];
    }
}
