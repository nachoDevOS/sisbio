<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
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
     * @var list<string>
     */
    private const CLAVE = ['ci', 'fecha', 'idTurno'];

    /**
     * Copia las licencias del SIA a la tabla local `licencias`. Idempotente:
     * reejecutarlo no duplica (upsert por ci+fecha+idTurno). Recorta el padding char().
     */
    public function handle(): int
    {
        $tamanoLote = max(1, (int) $this->option('chunk'));
        $destino = config('database.default');

        if ($destino === 'sia') {
            $this->error('La conexión por defecto es «sia»; no hay a dónde copiar.');

            return self::FAILURE;
        }

        $copiadas = 0;
        $actualizables = [...array_values(array_diff(self::MAPA, self::CLAVE)), 'updated_at'];

        try {
            DB::connection('sia')->table('Licencias')
                ->select(array_keys(self::MAPA))
                ->orderBy('IdPersona')
                ->chunk($tamanoLote, function ($filas) use (&$copiadas, $actualizables, $destino): void {
                    $ahora = now();

                    $registros = $filas
                        ->map(fn ($fila): array => $this->aLocal((array) $fila) + ['created_at' => $ahora, 'updated_at' => $ahora])
                        ->all();

                    DB::connection($destino)->table('licencias')
                        ->upsert($registros, self::CLAVE, $actualizables);

                    $copiadas += count($registros);
                });
        } catch (Throwable $e) {
            $this->error("Falló la migración de licencias: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Listo. {$copiadas} licencia(s) migrada(s) del SIA a «{$destino}».");

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
