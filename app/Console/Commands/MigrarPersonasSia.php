<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('sia:migrar-personas {--chunk=500 : Filas por lote}')]
#[Description('Copia los funcionarios (Personas) del SIA (SQL Server) a la base local, tal cual, sin tocar el origen.')]
class MigrarPersonasSia extends Command
{
    /**
     * Mapa columna del SIA (PascalCase) → columna local (camelCase). Se leen
     * del origen las claves y se escriben en local con los valores. El carnet
     * IdPersona pasa a `ci`.
     *
     * @var array<string, string>
     */
    private const MAPA = [
        'IdPersona' => 'ci',
        'OrigenId' => 'origenId',
        'Paterno' => 'paterno',
        'Materno' => 'materno',
        'Nombres' => 'nombres',
        'FechaNacimiento' => 'fechaNacimiento',
        'LugarNacimiento' => 'lugarNacimiento',
        'Sexo' => 'sexo',
        'EstadoCivil' => 'estadoCivil',
        'CodigoProfesion' => 'codigoProfesion',
        'NivelEstudio' => 'nivelEstudio',
        'Telefono' => 'telefono',
        'Direccion' => 'direccion',
        'CorreoE' => 'correo',
        'MarcaDirecta' => 'marcaDirecta',
        'PinReloj' => 'pinReloj',
    ];

    /**
     * Copia las Personas del SIA a la tabla local `personas`. Idempotente:
     * reejecutarlo no duplica (upsert por ci). El padding de los char() se recorta.
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
        // Al reejecutar se refresca updated_at, pero no created_at; deleted_at
        // nunca se toca desde aquí.
        $actualizables = [...array_values(array_diff(self::MAPA, ['ci'])), 'updated_at'];

        try {
            DB::connection('sia')->table('Personas')
                ->select(array_keys(self::MAPA))
                ->orderBy('IdPersona')
                ->chunk($tamanoLote, function ($filas) use (&$copiadas, $actualizables, $destino): void {
                    $ahora = now();

                    $registros = $filas
                        ->map(fn ($fila): array => $this->aLocal((array) $fila) + ['created_at' => $ahora, 'updated_at' => $ahora])
                        ->all();

                    DB::connection($destino)->table('personas')
                        ->upsert($registros, ['ci'], $actualizables);

                    $copiadas += count($registros);
                });
        } catch (Throwable $e) {
            $this->error("Falló la migración de funcionarios: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Listo. {$copiadas} funcionario(s) migrado(s) del SIA a «{$destino}».");

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
