<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('sia:migrar-profesiones {--chunk=500 : Filas por lote}')]
#[Description('Copia el catálogo de profesiones del SIA (SQL Server) a la base local, tal cual, sin tocar el origen.')]
class MigrarProfesionesSia extends Command
{
    /**
     * Mapa columna del SIA (PascalCase) → columna local (camelCase).
     *
     * @var array<string, string>
     */
    private const MAPA = [
        'CodigoProfesion' => 'codigoProfesion',
        'NombreProfesion' => 'nombreProfesion',
    ];

    /**
     * Copia las profesiones del SIA a la tabla local `profesiones`. Idempotente:
     * reejecutarlo no duplica (upsert por codigoProfesion). Recorta el padding char().
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

        try {
            DB::connection('sia')->table('Profesiones')
                ->select(array_keys(self::MAPA))
                ->orderBy('CodigoProfesion')
                ->chunk($tamanoLote, function ($filas) use (&$copiadas, $destino): void {
                    $ahora = now();

                    $registros = $filas
                        ->map(fn ($fila): array => $this->aLocal((array) $fila) + ['created_at' => $ahora, 'updated_at' => $ahora])
                        ->all();

                    DB::connection($destino)->table('profesiones')
                        ->upsert($registros, ['codigoProfesion'], ['nombreProfesion', 'updated_at']);

                    $copiadas += count($registros);
                });
        } catch (Throwable $e) {
            $this->error("Falló la migración de profesiones: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Listo. {$copiadas} profesión(es) migrada(s) del SIA a «{$destino}».");

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
