<?php

namespace App\Services;

use App\Models\Sia\Asistencia;
use App\Models\Sia\Persona;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * Registra marcaciones en la tabla Asistencia del SIA, desde cualquier fuente
 * (CSV importado o lectura en vivo de un equipo). Aplica siempre las mismas
 * reglas: cruza el ID del reloj contra Personas, descarta fechas basura del
 * RTC (futuras) y no duplica lo que ya está (misma IdPersona + Fecha + Hora).
 */
class RegistroAsistenciaSia
{
    /**
     * Procesa las filas y devuelve el conteo por resultado.
     *
     * @param  iterable<array{ci: ?string, momento: ?Carbon}>  $filas
     * @return array{insertadas: int, existentes: int, sinFuncionario: int, invalidas: int}
     */
    public function registrar(iterable $filas): array
    {
        $conteo = ['insertadas' => 0, 'existentes' => 0, 'sinFuncionario' => 0, 'invalidas' => 0];

        foreach ($filas as $fila) {
            $momento = $fila['momento'] ?? null;

            // Sin fecha/hora parseable: fila inválida (o encabezado ya filtrado
            // por quien arma las filas).
            if (! $momento instanceof Carbon) {
                $conteo['invalidas']++;

                continue;
            }

            $fecha = $momento->copy()->startOfDay();

            // El reloj arrastra fecha basura por la batería del RTC (años tipo
            // 2064/2103). Esas desbordan la columna Fecha del SIA; se descartan.
            if ($fecha->isFuture()) {
                $conteo['invalidas']++;

                continue;
            }

            $persona = (new Persona)->resolveRouteBinding(trim((string) ($fila['ci'] ?? '')));

            if (! $persona) {
                $conteo['sinFuncionario']++;

                continue;
            }

            $yaExiste = Asistencia::query()
                ->where('IdPersona', $persona->IdPersona)
                ->whereDate('Fecha', $fecha->toDateString())
                ->whereTime('Hora', $momento->format('H:i:s'))
                ->exists();

            if ($yaExiste) {
                $conteo['existentes']++;

                continue;
            }

            try {
                Asistencia::create([
                    'IdPersona' => $persona->IdPersona,
                    'Fecha' => $fecha,
                    // La Hora se guarda sobre la fecha base 1899-12-30, como el SIA real.
                    'Hora' => '1899-12-30 '.$momento->format('H:i:s'),
                    'Tipo' => Asistencia::TIPO_RELOJ,
                ]);

                $conteo['insertadas']++;
            } catch (QueryException) {
                // Dato legado que el SIA rechaza (otro campo fuera de rango):
                // se cuenta y se sigue con el resto.
                $conteo['invalidas']++;
            }
        }

        return $conteo;
    }

    /**
     * Arma el mensaje de resultado a partir del conteo.
     *
     * @param  array{insertadas: int, existentes: int, sinFuncionario: int, invalidas: int}  $conteo
     */
    public function mensaje(array $conteo, string $prefijo = 'Importación completa'): string
    {
        return "{$prefijo}: {$conteo['insertadas']} marcación(es) nueva(s), {$conteo['existentes']} ya existían, "
            ."{$conteo['sinFuncionario']} sin funcionario vinculado, {$conteo['invalidas']} fila(s) inválida(s).";
    }
}
