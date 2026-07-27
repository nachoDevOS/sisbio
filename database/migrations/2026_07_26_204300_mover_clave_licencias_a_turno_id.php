<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pasa la identidad de una licencia del código de turno del SIA (`idTurno`) a la
 * FK real `turno_id` → `turnos.id`.
 *
 * La columna `idTurno` **no se borra**: queda como dato histórico de lo que
 * trajo el SIA. Pasa a nullable porque el sistema ya no la escribe, y sale del
 * índice único; la clave natural pasa a (ci + fecha + turno_id), igual de única
 * porque `turnos.idTurno` no se repite.
 *
 * `turno_id` deja de ser nullable: sin él la fila no identifica ningún horario y
 * el índice único no serviría (en MySQL varios NULL cuentan como distintos).
 *
 * Solo corre sobre las bases armadas antes del cambio: la migración de creación
 * ya deja la tabla con esta forma.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->yaMigrada()) {
            return;
        }

        // Sin turno resuelto la fila no identifica horario y rompería el índice
        // único. En la base ya migrada del SIA no hay ninguna.
        DB::table('licencias')->whereNull('turno_id')->delete();

        Schema::table('licencias', function (Blueprint $table): void {
            $table->dropUnique(['ci', 'fecha', 'idTurno']);
        });

        Schema::table('licencias', function (Blueprint $table): void {
            $table->char('idTurno', 3)->nullable()->change();
            $table->unsignedBigInteger('turno_id')->nullable(false)->change();
            $table->unique(['ci', 'fecha', 'turno_id']);
        });
    }

    public function down(): void
    {
        if (! $this->yaMigrada()) {
            return;
        }

        // Repuebla el código del SIA en las filas que no lo tengan, para poder
        // volver a la clave vieja.
        DB::table('licencias')
            ->join('turnos', 'turnos.id', '=', 'licencias.turno_id')
            ->whereNull('licencias.idTurno')
            ->update(['licencias.idTurno' => DB::raw('turnos.idTurno')]);

        Schema::table('licencias', function (Blueprint $table): void {
            $table->dropUnique(['ci', 'fecha', 'turno_id']);
        });

        Schema::table('licencias', function (Blueprint $table): void {
            $table->char('idTurno', 3)->nullable(false)->change();
            $table->unsignedBigInteger('turno_id')->nullable()->change();
            $table->unique(['ci', 'fecha', 'idTurno']);
        });
    }

    /**
     * ¿La tabla ya usa la clave por turno_id? Se mira el índice, no la columna:
     * `idTurno` sigue existiendo en las dos formas.
     */
    private function yaMigrada(): bool
    {
        return collect(Schema::getIndexes('licencias'))
            ->contains(fn (array $indice): bool => $indice['columns'] === ['ci', 'fecha', 'turno_id']);
    }
};
