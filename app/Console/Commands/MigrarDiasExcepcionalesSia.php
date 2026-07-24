<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('sia:migrar-dias-excepcionales {--chunk=500 : Filas por lote}')]
#[Description('Copia los días excepcionales (Calendario) del SIA (SQL Server) a la base local, tal cual, sin tocar el origen.')]
class MigrarDiasExcepcionalesSia extends Command
{
    /**
     * Mapa columna del SIA (PascalCase) → columna local (camelCase). En el SIA
     * la tabla se llama Calendario.
     *
     * @var array<string, string>
     */
    private const MAPA = [
        'Fecha' => 'fecha',
        'MotivoInasistencia' => 'motivoInasistencia',
    ];

    /**
     * Copia los días excepcionales del SIA a la tabla local `dias_excepcionales`.
     * Idempotente: reejecutarlo no duplica (upsert por fecha). Recorta el padding char().
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
        $lote = [];

        try {
            $filas = DB::connection('sia')->table('Calendario')
                ->select(array_keys(self::MAPA))
                ->cursor();

            foreach ($filas as $fila) {
                $ahora = now();
                $lote[] = $this->aLocal((array) $fila) + ['created_at' => $ahora, 'updated_at' => $ahora];

                if (count($lote) >= $tamanoLote) {
                    DB::connection($destino)->table('dias_excepcionales')->upsert($lote, ['fecha'], ['motivoInasistencia', 'updated_at']);
                    $copiadas += count($lote);
                    $lote = [];
                    $this->info("Copiados {$copiadas} día(s)…");
                }
            }

            if ($lote !== []) {
                DB::connection($destino)->table('dias_excepcionales')->upsert($lote, ['fecha'], ['motivoInasistencia', 'updated_at']);
                $copiadas += count($lote);
            }
        } catch (Throwable $e) {
            $this->error("Falló la migración de días excepcionales: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Listo. {$copiadas} día(s) excepcional(es) migrado(s) del SIA a «{$destino}».");

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
