<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Da de baja las columnas de eliminación lógica de `asistencias`.
 *
 * La migración que crea la tabla ya no las emite, así que una instalación nueva
 * nunca las tiene; esta existe para las bases que se crearon antes —desarrollo y
 * el servidor— y por eso cada paso se pregunta primero si hay algo que dar de
 * baja. Corrida dos veces, la segunda no hace nada.
 *
 * Las marcaciones no se dan de baja desde el sistema: entran del reloj o de un
 * CSV y se corrigen. `registerUser_id` se queda —esa es la auditoría de alta, y
 * la marcación manual sí se registra a nombre de quien la carga—.
 *
 * `up()` no es reversible del todo: `down()` devuelve las tres columnas, pero
 * vacías. Lo que había en ellas no se puede recuperar; tampoco había nada, nunca
 * se dio de baja una marcación.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('asistencias', 'deleteUser_id')) {
            Schema::table('asistencias', function (Blueprint $table): void {
                $table->dropForeign(['deleteUser_id']);
            });
        }

        $columnas = array_values(array_filter(
            ['deleted_at', 'deleteUser_id', 'deleteObservacion'],
            fn (string $columna): bool => Schema::hasColumn('asistencias', $columna)
        ));

        if ($columnas === []) {
            return;
        }

        Schema::table('asistencias', function (Blueprint $table) use ($columnas): void {
            $table->dropColumn($columnas);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('asistencias', 'deleted_at')) {
            return;
        }

        Schema::table('asistencias', function (Blueprint $table): void {
            $table->softDeletes();
            $table->foreignId('deleteUser_id')->nullable()->constrained('users');
            $table->text('deleteObservacion')->nullable();
        });
    }
};
