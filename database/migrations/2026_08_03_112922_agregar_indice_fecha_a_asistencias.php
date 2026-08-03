<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice por fecha en `asistencias` (4,4 millones de filas).
 *
 * La tabla venía con `unique(ci, fecha, hora)` e `index(ci)`. Por la regla del
 * prefijo izquierdo, ninguno de los dos sirve cuando se filtra SOLO por fecha
 * sin carnet, que es justo lo que hacen el listado de marcaciones (su filtro
 * por defecto es «del 1.º del mes hasta hoy») y las tarjetas del escritorio:
 *
 *     EXPLAIN ... WHERE fecha >= ? AND fecha < ?
 *     type: ALL   key: NULL   rows: 4.292.299     ← recorría la tabla entera
 *
 * Va `(fecha, ci)` y no solo `(fecha)` porque la tarjeta «personas que marcaron
 * hoy» es un COUNT(DISTINCT ci) sobre un rango de fechas: con las dos columnas
 * el índice cubre la consulta y no necesita ir a la tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table): void {
            $table->index(['fecha', 'ci'], 'asistencias_fecha_ci_index');
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table): void {
            $table->dropIndex('asistencias_fecha_ci_index');
        });
    }
};
