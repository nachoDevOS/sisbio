<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla local (MySQL) que replica «Profesiones» del SIA (SQL Server 2008 R2):
 * el catálogo de profesiones. Mismos campos, en camelCase.
 *
 * Igual que personas: id autoincremental, timestamps y eliminación lógica.
 * codigoProfesion (PK en el SIA) pasa a columna única: clave del upsert y del
 * join con personas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesiones', function (Blueprint $table): void {
            $table->id();
            $table->char('codigoProfesion', 2)->unique();
            $table->string('nombreProfesion', 60);

            $table->text('observacion')->nullable();
            $table->smallInteger('estado')->default(1);

            $table->timestamps();
            $table->foreignId('registerUser_id')->nullable()->constrained('users');

            $table->softDeletes();
            $table->foreignId('deleteUser_id')->nullable()->constrained('users');
            $table->text('deleteObservacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesiones');
    }
};
