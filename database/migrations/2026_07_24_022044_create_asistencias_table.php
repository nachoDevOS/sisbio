<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla local (MySQL) que replica «Asistencia» del SIA (SQL Server 2008 R2):
 * las marcaciones de los funcionarios. Mismos campos, en camelCase.
 *
 * Suma id autoincremental y timestamps. A diferencia del resto de las tablas,
 * NO lleva eliminación lógica ni columnas de auditoría de baja: las marcaciones
 * no se dan de baja desde el sistema —entran del reloj o de un CSV y se
 * corrigen, no se borran—, y el `deleted_at IS NULL` que agregaba Eloquent
 * costaba caro sobre 4,4 millones de filas (le impide a MySQL usar la
 * optimización de MIN/MAX sobre el índice).
 *
 * El carnet va en `ci` (en el SIA es IdPersona). En el SIA la clave es compuesta
 * (IdPersona + Fecha + Hora); aquí eso pasa a un índice único (ci + fecha + hora)
 * que sirve de clave natural para el upsert idempotente. `ci` también se indexa
 * aparte para los joins con personas (sin FK: el legado tiene marcaciones
 * huérfanas). `hora` guarda solo la hora sobre la fecha base 1899-12-30.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table): void {
            $table->id();
            $table->char('ci', 12);
            $table->dateTime('fecha');
            $table->dateTime('hora');
            $table->char('tipo', 1);

            $table->text('observacion')->nullable();
            $table->smallInteger('estado')->default(1);

            $table->timestamps();
            $table->foreignId('registerUser_id')->nullable()->constrained('users');

            $table->unique(['ci', 'fecha', 'hora']);
            $table->index('ci');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
