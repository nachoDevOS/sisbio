<?php

use App\Models\Sia\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // SIA falso (sqlite en memoria) con la tabla Personas del sistema legado.
    // La base local (default) trae la tabla `personas` de la migración propia.
    fakeSiaDatabase();
});

test('copia los funcionarios del SIA a la base local con id y timestamps', function () {
    Persona::factory()->count(3)->create();

    $this->artisan('sia:migrar-personas')->assertSuccessful();

    expect(DB::table('personas')->count())->toBe(3);

    // La copia local tiene su propio id autoincremental y timestamps poblados.
    $fila = DB::table('personas')->first();
    expect($fila->id)->toBeGreaterThan(0)
        ->and($fila->created_at)->not->toBeNull()
        ->and($fila->updated_at)->not->toBeNull();
});

test('mapea IdPersona→ci y preserva los campos, recortando el relleno de los char()', function () {
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '12345678    ', // char(12) del SIA, con relleno; en local va a ci.
        'Paterno' => 'Perez',
        'Materno' => 'Gomez',
        'Nombres' => 'Juan',
        'Sexo' => 'M',
        'CodigoProfesion' => '01',
        'PinReloj' => '8123',
        'MarcaDirecta' => false,
    ]);

    $this->artisan('sia:migrar-personas')->assertSuccessful();

    $local = DB::table('personas')->where('ci', '12345678')->first();

    expect($local)->not->toBeNull()
        ->and($local->nombres)->toBe('Juan')
        ->and($local->paterno)->toBe('Perez')
        ->and($local->sexo)->toBe('M')
        ->and($local->codigoProfesion)->toBe('01')
        ->and($local->pinReloj)->toBe('8123');
});

test('es idempotente: correrlo dos veces no duplica', function () {
    Persona::factory()->count(2)->create();

    $this->artisan('sia:migrar-personas')->assertSuccessful();
    $this->artisan('sia:migrar-personas')->assertSuccessful();

    expect(DB::table('personas')->count())->toBe(2);
});

test('actualiza los cambios del origen al reejecutar', function () {
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '999',
        'Paterno' => 'Vieja',
        'Nombres' => 'Ana',
        'MarcaDirecta' => false,
    ]);
    $this->artisan('sia:migrar-personas')->assertSuccessful();

    DB::connection('sia')->table('Personas')->where('IdPersona', '999')->update(['Paterno' => 'Nueva']);
    $this->artisan('sia:migrar-personas')->assertSuccessful();

    expect(DB::table('personas')->where('ci', '999')->value('paterno'))->toBe('Nueva');
});

test('no escribe sobre la base del SIA (origen intacto)', function () {
    Persona::factory()->count(2)->create();

    $this->artisan('sia:migrar-personas')->assertSuccessful();

    expect(DB::connection('sia')->table('Personas')->count())->toBe(2);
});
