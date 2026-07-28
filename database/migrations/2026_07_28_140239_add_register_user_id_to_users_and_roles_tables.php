<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `registerUser_id` en `users` y `roles`: quién dio de alta la fila.
 *
 * El trait RegistersUserEvents escribe la columna en todos los modelos que lo
 * usan, así que las dos tablas que venían del esqueleto de Laravel y de
 * spatie/permission también tienen que tenerla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('registerUser_id')->nullable()->constrained('users');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->foreignId('registerUser_id')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['registerUser_id']);
            $table->dropColumn('registerUser_id');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropForeign(['registerUser_id']);
            $table->dropColumn('registerUser_id');
        });
    }
};
