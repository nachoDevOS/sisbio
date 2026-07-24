<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    // Aísla la conexión 'sia' del SQL Server real en TODOS los tests: por
    // defecto apunta a un sqlite vacío, así ningún test pega a la red del SIA
    // (p. ej. al renderizar el dashboard). Los tests que necesitan datos del
    // SIA llaman fakeSiaDatabase(), que además crea las tablas.
    ->beforeEach(function (): void {
        config()->set('database.connections.sia', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('sia');

        // Mamoré (API externa) desactivada por defecto: ningún test pega a la red
        // salvo los que la configuran y falsean la respuesta con Http::fake().
        config()->set('services.mamore', ['url' => null, 'key' => null]);

        // Caché limpia por test (evita arrastrar nombres de Mamoré cacheados).
        Cache::flush();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Crea un usuario con el rol super_admin (acceso total al sistema).
 */
function asSuperAdmin(): User
{
    $role = Role::firstOrCreate([
        'name' => 'super_admin',
        'guard_name' => 'web',
    ]);

    return User::factory()->create()->assignRole($role);
}

/**
 * Reemplaza la conexión 'sia' (SQL Server 2008 remoto) por un sqlite en
 * memoria con las tablas del módulo de asistencia, para probar sin red.
 */
function fakeSiaDatabase(): void
{
    config()->set('database.connections.sia', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);

    DB::purge('sia');
    Cache::flush();

    Schema::connection('sia')->create('Personas', function (Blueprint $tabla): void {
        $tabla->string('IdPersona', 12)->primary();
        $tabla->string('OrigenId', 3)->nullable();
        $tabla->string('Paterno', 25);
        $tabla->string('Materno', 25)->nullable();
        $tabla->string('Nombres', 35);
        $tabla->dateTime('FechaNacimiento')->nullable();
        $tabla->string('LugarNacimiento', 25)->nullable();
        $tabla->string('Sexo', 1)->nullable();
        $tabla->string('EstadoCivil', 1)->nullable();
        $tabla->string('CodigoProfesion', 2)->nullable();
        $tabla->string('NivelEstudio', 20)->nullable();
        $tabla->string('Telefono', 20)->nullable();
        $tabla->string('Direccion', 40)->nullable();
        $tabla->string('CorreoE', 40)->nullable();
        // Sin default: igual que en el SQL Server real, el INSERT debe
        // mandar siempre MarcaDirecta o falla por NOT NULL.
        $tabla->boolean('MarcaDirecta');
        $tabla->string('PinReloj', 10)->nullable();
    });

    Schema::connection('sia')->create('Profesiones', function (Blueprint $tabla): void {
        $tabla->string('CodigoProfesion', 2)->primary();
        $tabla->string('NombreProfesion', 60);
    });

    Schema::connection('sia')->create('Asistencia', function (Blueprint $tabla): void {
        $tabla->string('IdPersona', 12);
        $tabla->dateTime('Fecha');
        $tabla->dateTime('Hora');
        $tabla->string('Tipo', 1);
    });

    Schema::connection('sia')->create('DiaTurnos', function (Blueprint $tabla): void {
        $tabla->string('IdTurno', 3)->primary();
        $tabla->string('Dia', 1);
        $tabla->string('NombreTurno', 25);
        $tabla->dateTime('HEntrada');
        $tabla->dateTime('HSalida');
        $tabla->dateTime('HTolerancia');
        $tabla->dateTime('EMinima');
        $tabla->dateTime('EMaxima');
        $tabla->dateTime('SMinima');
        $tabla->dateTime('SMaxima');
        $tabla->dateTime('STolerancia');
        $tabla->decimal('HTrabajadas', 19, 4);
        $tabla->boolean('SiguienteDia');
    });

    Schema::connection('sia')->create('Licencias', function (Blueprint $tabla): void {
        $tabla->dateTime('FechaPedido');
        $tabla->string('Usuario', 50);
        $tabla->dateTime('Fecha');
        $tabla->string('IdPersona', 12);
        $tabla->string('IdTurno', 3);
        $tabla->dateTime('LEntra')->nullable();
        $tabla->dateTime('LSale')->nullable();
        $tabla->boolean('TCompleto');
        $tabla->string('Motivo', 255)->nullable();
        $tabla->boolean('GoceHaberes');
    });

    Schema::connection('sia')->create('AsignacionTurnos', function (Blueprint $tabla): void {
        $tabla->string('IdPersona', 12);
        $tabla->string('IdTurno', 3);
        $tabla->dateTime('Desde');
        $tabla->dateTime('Hasta');
    });

    Schema::connection('sia')->create('Calendario', function (Blueprint $tabla): void {
        $tabla->dateTime('Fecha');
        $tabla->string('MotivoInasistencia', 255)->nullable();
    });
}
