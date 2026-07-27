<?php

namespace Database\Factories;

use App\Models\AsignacionTurno;
use App\Models\Turno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsignacionTurno>
 */
class AsignacionTurnoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ci' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            // El código del SIA sale del turno vinculado, para que el par
            // idTurno/turno_id sea coherente como en la migración real.
            'turno_id' => Turno::factory(),
            'idTurno' => fn (array $atributos): string => (string) (Turno::find($atributos['turno_id'])?->idTurno ?? '001'),
            'desde' => now()->subYear()->startOfDay(),
            'hasta' => now()->addYear()->startOfDay(),
            'observacion' => null,
            'estado' => 1,
        ];
    }

    /**
     * Asignación ya vencida (no admite licencias nuevas).
     */
    public function vencida(): self
    {
        return $this->state(fn (): array => [
            'desde' => now()->subYears(2)->startOfDay(),
            'hasta' => now()->subMonth()->startOfDay(),
        ]);
    }
}
