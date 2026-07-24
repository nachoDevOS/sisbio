<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla local (MySQL) que replica «Personas» del SIA (SQL Server 2008 R2),
 * campo por campo, para migrar los funcionarios a la base propia del sistema.
 *
 * Nombres locales en camelCase (el origen usa PascalCase; el comando de copia
 * mapea). A diferencia del SIA, la copia local tiene id autoincremental,
 * timestamps y eliminación lógica. El carnet va en la columna única `ci`
 * (en el SIA se llama IdPersona): sigue siendo la clave de negocio del upsert
 * y de los joins con asistencias.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table): void {
            $table->id();
            $table->char('ci', 12)->unique();
            $table->char('origenId', 3)->nullable();
            $table->string('paterno', 25);
            $table->string('materno', 25)->nullable();
            $table->string('nombres', 35);
            $table->dateTime('fechaNacimiento')->nullable();
            $table->string('lugarNacimiento', 25)->nullable();
            $table->char('sexo', 1)->nullable();
            $table->char('estadoCivil', 1)->nullable();
            $table->char('codigoProfesion', 2)->nullable();
            $table->string('nivelEstudio', 20)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 40)->nullable();
            $table->string('correo', 40)->nullable();
            // Sin default, igual que el SQL Server real: el INSERT debe mandar
            // siempre marcaDirecta o falla por NOT NULL.
            $table->boolean('marcaDirecta');
            $table->string('pinReloj', 10)->nullable();

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
        Schema::dropIfExists('personas');
    }
};
