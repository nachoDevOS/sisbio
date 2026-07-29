<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('sia:migrar-licencias {--chunk=500 : Filas por lote}')]
#[Description('Copia las licencias/permisos del SIA (SQL Server) a la base local, tal cual, sin tocar el origen.')]
class MigrarLicenciasSia extends Command
{
    /**
     * Mapa columna del SIA (PascalCase) → columna local (camelCase). El carnet
     * IdPersona pasa a `ci`.
     *
     * @var array<string, string>
     */
    private const MAPA = [
        'FechaPedido' => 'fechaPedido',
        'Usuario' => 'usuario',
        'Fecha' => 'fecha',
        'IdPersona' => 'ci',
        'IdTurno' => 'idTurno',
        'LEntra' => 'lEntra',
        'LSale' => 'lSale',
        'TCompleto' => 'tCompleto',
        'Motivo' => 'motivo',
        'GoceHaberes' => 'goceHaberes',
    ];

    /**
     * Clave natural de una licencia (una por funcionario, día y turno). Es por
     * lo que deduplica el upsert. Solo columnas NOT NULL: en un índice único de
     * MySQL, varios NULL cuentan como distintos y romperían la idempotencia.
     *
     * El turno va por la FK `turno_id`, no por el código del SIA: `idTurno` se
     * copia pero solo como dato histórico, ya no identifica la fila.
     *
     * @var list<string>
     */
    private const CLAVE = ['ci', 'fecha', 'turno_id'];

    /**
     * Copia las licencias del SIA a la tabla local `licencias`. Idempotente:
     * reejecutarlo no duplica (upsert por ci+fecha+turno_id). Recorta el padding char().
     *
     * Se lee con un cursor (stream de una sola consulta) en vez de paginar: el
     * ROW_NUMBER() del grammar 2008 haría que cada página reescanee (O(n²)) y en
     * tablas grandes el comando parecería colgado. Imprime progreso por lote.
     */
    public function handle(): int
    {
        $tamanoLote = max(1, (int) $this->option('chunk'));
        $destino = config('database.default');

        if ($destino === 'sia') {
            $this->error('La conexión por defecto es «sia»; no hay a dónde copiar.');

            return self::FAILURE;
        }

        // Mapa idTurno → id local de turnos, para resolver la FK turno_id sin
        // consultar la base por cada fila. Si turnos está vacío, todas quedan null.
        $turnosPorCodigo = DB::connection($destino)->table('turnos')->pluck('id', 'idTurno');

        if ($turnosPorCodigo->isEmpty()) {
            $this->error('La tabla «turnos» está vacía: sin ella no se puede resolver el turno de cada licencia. Corré «sia:migrar-horarios» antes.');

            return self::FAILURE;
        }

        $actualizables = [...array_values(array_diff(self::MAPA, self::CLAVE)), 'updated_at'];
        $copiadas = 0;
        $salteadas = 0;
        $lote = [];

        try {
            $filas = DB::connection('sia')->table('Licencias')
                ->select(array_keys(self::MAPA))
                ->cursor();

            foreach ($filas as $fila) {
                $ahora = now();
                $local = $this->aLocal((array) $fila);

                // FK real: id de MySQL del turno cuyo idTurno coincide. El
                // `idTurno` se conserva en la fila solo como dato histórico.
                $turnoId = $turnosPorCodigo[$local['idTurno']] ?? null;

                // Sin turno la fila no identifica ningún horario y turno_id es
                // NOT NULL: se saltea y se informa al final.
                if ($turnoId === null) {
                    $salteadas++;

                    continue;
                }

                $local['turno_id'] = $turnoId;
                $lote[] = $local + ['created_at' => $ahora, 'updated_at' => $ahora];

                if (count($lote) >= $tamanoLote) {
                    DB::connection($destino)->table('licencias')->upsert($lote, self::CLAVE, $actualizables);
                    $copiadas += count($lote);
                    $lote = [];
                    $this->info("Copiadas {$copiadas} licencia(s)…");
                }
            }

            if ($lote !== []) {
                DB::connection($destino)->table('licencias')->upsert($lote, self::CLAVE, $actualizables);
                $copiadas += count($lote);
            }
        } catch (Throwable $e) {
            $this->error("Falló la migración de licencias: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Listo. {$copiadas} licencia(s) migrada(s) del SIA a «{$destino}».");

        if ($salteadas > 0) {
            $this->warn("{$salteadas} licencia(s) salteada(s): su IdTurno no existe en «turnos».");
        }

        return self::SUCCESS;
    }

    /**
     * Traduce una fila del SIA a la fila local (renombra columnas, recorta el
     * padding de los char() y descarta la hora de `fecha`).
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

        // En el SIA «Fecha» es datetime (siempre a las 00:00) pero acá la columna
        // es date: si alguna fila trajera hora, MySQL en modo estricto rechazaría
        // el insert por truncamiento.
        if ($local['fecha'] !== null) {
            $local['fecha'] = Carbon::parse($local['fecha'])->toDateString();
        }

        return $local;
    }
}
