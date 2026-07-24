<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla local (MySQL) que replica «Licencias» del SIA (SQL Server 2008 R2):
 * permisos/licencias de los funcionarios (comisiones, vacaciones, etc.).
 * Mismos campos que la tabla legada, en camelCase.
 *
 * Igual que el resto de la migración SIA→MySQL: id autoincremental, timestamps
 * y eliminación lógica propios. El carnet va en `ci` (en el SIA es IdPersona).
 * La clave natural (una licencia por funcionario, día y turno) pasa a un índice
 * único (ci + fecha + idTurno) para el upsert idempotente; `ci` se indexa aparte
 * para los joins con personas. Se conserva `idTurno` (código del SIA) y además
 * se agrega la FK `turno_id` → `turnos.id`, que el comando resuelve cruzando
 * idTurno contra `turnos` (por eso los horarios se migran antes). `lEntra`/`lSale`
 * guardan la hora sobre la fecha base 1899-12-30, como el resto de horas del SIA.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencias', function (Blueprint $table): void {
            $table->id();
            $table->dateTime('fechaPedido');
            $table->string('usuario', 50);
            $table->dateTime('fecha');
            $table->char('ci', 12);
            $table->char('idTurno', 3);
            $table->foreignId('turno_id')->nullable()->constrained('turnos');
            $table->dateTime('lEntra')->nullable();
            $table->dateTime('lSale')->nullable();
            $table->boolean('tCompleto');
            $table->string('motivo', 255)->nullable();
            $table->boolean('goceHaberes');

            $table->text('observacion')->nullable();
            $table->smallInteger('estado')->default(1);

            $table->timestamps();
            $table->foreignId('registerUser_id')->nullable()->constrained('users');

            $table->softDeletes();
            $table->foreignId('deleteUser_id')->nullable()->constrained('users');
            $table->text('deleteObservacion')->nullable();

            $table->unique(['ci', 'fecha', 'idTurno']);
            $table->index('ci');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencias');
    }
};
