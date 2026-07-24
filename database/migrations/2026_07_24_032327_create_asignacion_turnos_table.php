<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla local (MySQL) que replica «AsignacionTurnos» del SIA (SQL Server 2008 R2):
 * qué turno tiene asignado cada funcionario en un rango de fechas. Campos en
 * camelCase, con id/timestamps/eliminación lógica propios.
 *
 * El carnet va en `ci` (en el SIA es IdPersona). Se conserva `idTurno` (el
 * código de turno del SIA) y además se agrega la FK real `turno_id` → `turnos.id`,
 * que el comando de copia resuelve cruzando `idTurno` contra la tabla `turnos`
 * ya migrada. Por eso `turnos` debe migrarse antes; si un idTurno no cruza,
 * `turno_id` queda null. Clave natural (upsert): ci + idTurno + desde.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignacion_turnos', function (Blueprint $table): void {
            $table->id();
            $table->char('ci', 12);
            $table->char('idTurno', 3);
            $table->foreignId('turno_id')->nullable()->constrained('turnos');
            $table->dateTime('desde');
            $table->dateTime('hasta');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ci', 'idTurno', 'desde']);
            $table->index('ci');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion_turnos');
    }
};
