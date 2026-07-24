<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('sia:migrar-asignacion-turnos {--chunk=500 : Filas por lote}')]
#[Description('Copia las asignaciones de turno del SIA (SQL Server) a la base local, resolviendo la FK turno_id.')]
class MigrarAsignacionTurnosSia extends Command
{
    /**
     * Mapa columna del SIA (PascalCase) → columna local (camelCase). El carnet
     * IdPersona pasa a `ci`; IdTurno se conserva como `idTurno`. La FK `turno_id`
     * no viene del SIA: se resuelve cruzando idTurno contra `turnos` (ver handle()).
     *
     * @var array<string, string>
     */
    private const MAPA = [
        'IdPersona' => 'ci',
        'IdTurno' => 'idTurno',
        'Desde' => 'desde',
        'Hasta' => 'hasta',
    ];

    /**
     * Clave natural (upsert): una asignación por funcionario, turno y fecha de
     * inicio. Solo columnas NOT NULL.
     *
     * @var list<string>
     */
    private const CLAVE = ['ci', 'idTurno', 'desde'];

    /**
     * Copia las asignaciones del SIA a la tabla local `asignacion_turnos`.
     * Idempotente (upsert por ci+idTurno+desde). Además de renombrar columnas,
     * resuelve `turno_id` cruzando el idTurno de cada fila contra la tabla
     * local `turnos` (usa su id de MySQL). Por eso conviene migrar los horarios
     * antes; si un idTurno no cruza, turno_id queda null.
     */
    public function handle(): int
    {
        $tamanoLote = max(1, (int) $this->option('chunk'));
        $destino = config('database.default');

        if ($destino === 'sia') {
            $this->error('La conexión por defecto es «sia»; no hay a dónde copiar.');

            return self::FAILURE;
        }

        // Mapa idTurno → id local de turnos, para resolver la FK sin consultar
        // la base por cada fila. Si turnos está vacío, todas las FK quedan null.
        $turnosPorCodigo = DB::connection($destino)->table('turnos')->pluck('id', 'idTurno');

        if ($turnosPorCodigo->isEmpty()) {
            $this->warn('La tabla «turnos» está vacía: turno_id quedará null. Corré «sia:migrar-horarios» antes.');
        }

        $copiadas = 0;

        try {
            DB::connection('sia')->table('AsignacionTurnos')
                ->select(array_keys(self::MAPA))
                ->orderBy('IdPersona')
                ->chunk($tamanoLote, function ($filas) use (&$copiadas, $destino, $turnosPorCodigo): void {
                    $ahora = now();

                    $registros = $filas->map(function ($fila) use ($ahora, $turnosPorCodigo): array {
                        $local = $this->aLocal((array) $fila);
                        // FK real: id de MySQL del turno cuyo idTurno coincide.
                        $local['turno_id'] = $turnosPorCodigo[$local['idTurno']] ?? null;

                        return $local + ['created_at' => $ahora, 'updated_at' => $ahora];
                    })->all();

                    DB::connection($destino)->table('asignacion_turnos')
                        ->upsert($registros, self::CLAVE, ['turno_id', 'hasta', 'updated_at']);

                    $copiadas += count($registros);
                });
        } catch (Throwable $e) {
            $this->error("Falló la migración de asignaciones de turno: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Listo. {$copiadas} asignación(es) migrada(s) del SIA a «{$destino}».");

        return self::SUCCESS;
    }

    /**
     * Traduce una fila del SIA a la fila local (renombra columnas y recorta el
     * padding de los char()).
     *
     * @param  array<string, mixed>  $fila
     * @return array<string, mixed>
     */
    private function aLocal(array $fila): array
    {
        $local = [];

        foreach (self::MAPA as $origen => $destino) {
            $valor = $fila[$origen] ?? null;
            $local[$destino] = is_string($valor) ? trim($valor) : $valor;
        }

        return $local;
    }
}
