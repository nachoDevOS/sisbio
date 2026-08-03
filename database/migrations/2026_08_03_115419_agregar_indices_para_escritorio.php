<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices que faltaban en las tablas migradas del SIA.
 *
 * Todas se trajeron con la misma forma: un único compuesto que arranca por
 * `ci` más un índice por `ci` suelto. Sirven para «lo de esta persona», que es
 * como consultaba el sistema viejo. Pero el escritorio —y varios listados—
 * preguntan al revés: «lo de esta fecha», sin carnet. Por la regla del prefijo
 * izquierdo ninguno de los índices existentes entra, y MySQL recorre la tabla.
 *
 * Medido antes de agregarlos, sobre los datos reales:
 *
 *     licencias del día          271 ms   (1.109.023 filas)
 *     turnos vigentes hoy        140 ms   (  420.292 filas)
 *     próximo día excepcional     23 ms   (    7.517 filas)
 *
 * En `asignacion_turnos` el índice va por `(hasta, desde)` y no al revés: la
 * vigencia se pregunta siempre «que no haya terminado todavía», y arrancar por
 * `hasta` descarta de una toda la historia vencida, que es la mayor parte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licencias', function (Blueprint $table): void {
            $table->index('fecha', 'licencias_fecha_index');
        });

        Schema::table('asignacion_turnos', function (Blueprint $table): void {
            $table->index(['hasta', 'desde'], 'asignacion_turnos_vigencia_index');
        });

        Schema::table('dias_excepcionales', function (Blueprint $table): void {
            $table->index('fecha', 'dias_excepcionales_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::table('licencias', function (Blueprint $table): void {
            $table->dropIndex('licencias_fecha_index');
        });

        Schema::table('asignacion_turnos', function (Blueprint $table): void {
            $table->dropIndex('asignacion_turnos_vigencia_index');
        });

        Schema::table('dias_excepcionales', function (Blueprint $table): void {
            $table->dropIndex('dias_excepcionales_fecha_index');
        });
    }
};
