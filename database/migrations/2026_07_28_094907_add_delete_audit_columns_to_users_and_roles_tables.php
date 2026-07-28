<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría de baja en `users` y `roles`: quién eliminó y por qué.
 *
 * El resto de las tablas del sistema ya traía estas columnas desde su propia
 * migración; estas dos quedaron afuera porque vienen del esqueleto de Laravel y
 * de spatie/permission. Con ellas, el trait RegistersUserEvents también graba
 * acá el motivo que se escribe en el modal de eliminación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('deleteUser_id')->nullable()->constrained('users');
            $table->text('deleteObservacion')->nullable();
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->foreignId('deleteUser_id')->nullable()->constrained('users');
            $table->text('deleteObservacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['deleteUser_id']);
            $table->dropColumn(['deleteUser_id', 'deleteObservacion']);
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropForeign(['deleteUser_id']);
            $table->dropColumn(['deleteUser_id', 'deleteObservacion']);
        });
    }
};
