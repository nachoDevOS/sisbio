<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla local (MySQL) que replica «Calendario» del SIA (SQL Server 2008 R2):
 * los días excepcionales (feriados, tolerancias, motivos de inasistencia).
 * En el SIA la tabla se llama Calendario; acá se renombra a `dias_excepcionales`.
 *
 * Campos en camelCase, con id/timestamps/eliminación lógica propios. Una fila
 * por fecha, por eso `fecha` es la clave natural (índice único) del upsert.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dias_excepcionales', function (Blueprint $table): void {
            $table->id();
            $table->dateTime('fecha')->unique();
            $table->string('motivoInasistencia', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dias_excepcionales');
    }
};
