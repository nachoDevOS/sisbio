<?php

namespace Database\Factories;

use App\Models\Equipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipo>
 */
class EquipoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Equipo '.fake()->unique()->numberBetween(1, 9999),
            'ip' => fake()->unique()->localIpv4(),
            'puerto' => 4370,
            'comm_key' => 0,
            'ubicacion' => fake()->randomElement(['Entrada', 'Puerta principal', 'Bodega', 'Oficina']),
            'algoritmo' => null,
            'es_master' => false,
            'en_linea' => false,
            'ultima_sync' => null,
            'activo' => true,
        ];
    }

    /**
     * Estado: equipo marcado como maestro (origen de huellas).
     */
    public function master(): static
    {
        return $this->state(fn (): array => ['es_master' => true]);
    }
}
