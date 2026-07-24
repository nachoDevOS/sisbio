<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla local (MySQL) que replica «Asistencia» del SIA (SQL Server 2008 R2):
 * las marcaciones de los funcionarios. Mismos campos, en camelCase.
 *
 * Igual que personas, suma id autoincremental, timestamps y eliminación lógica.
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

            $table->softDeletes();
            $table->foreignId('deleteUser_id')->nullable()->constrained('users');
            $table->text('deleteObservacion')->nullable();

            $table->unique(['ci', 'fecha', 'hora']);
            $table->index('ci');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
