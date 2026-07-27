<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\EquipoAuditoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipoAuditoria>
 */
class EquipoAuditoriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $equipo = Equipo::factory()->create();

        return [
            'equipo_id' => $equipo->id,
            'accion' => EquipoAuditoria::ACCION_EXPORTAR,
            'motivo' => null,
            'datos_equipo' => [
                'id' => $equipo->id,
                'nombre' => $equipo->nombre,
                'ip' => $equipo->ip,
                'puerto' => $equipo->puerto,
                'ubicacion' => $equipo->ubicacion,
                'algoritmo' => $equipo->algoritmo,
                'es_master' => false,
                'en_linea' => false,
                'activo' => true,
                'ultima_sync' => null,
            ],
            'total_marcaciones' => fake()->numberBetween(0, 500),
            'desde' => null,
            'hasta' => null,
            'detalle' => null,
            'exito' => true,
            'ip_usuario' => fake()->localIpv4(),
        ];
    }

    /**
     * Estado: limpieza del reloj (acción destructiva, siempre con motivo).
     */
    public function limpieza(): static
    {
        return $this->state(fn (): array => [
            'accion' => EquipoAuditoria::ACCION_LIMPIAR,
            'motivo' => 'Memoria del equipo llena.',
        ]);
    }

    /**
     * Estado: baja del equipo (acción destructiva, siempre con motivo).
     */
    public function baja(): static
    {
        return $this->state(fn (): array => [
            'accion' => EquipoAuditoria::ACCION_ELIMINAR,
            'motivo' => 'Equipo dado de baja por falla de hardware.',
            'total_marcaciones' => null,
        ]);
    }
}
