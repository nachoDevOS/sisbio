<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    fakeSiaDatabase();
    $this->actingAs(asSuperAdmin());
});

test('el listado muestra las marcaciones del rango por defecto', function () {
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '777', 'Paterno' => 'Diaz', 'Materno' => null, 'Nombres' => 'Eva', 'PinReloj' => null, 'MarcaDirecta' => false,
    ]);
    DB::connection('sia')->table('Asistencia')->insert([
        'IdPersona' => '777',
        'Fecha' => now()->startOfDay()->toDateTimeString(),
        'Hora' => now()->toDateTimeString(),
        'Tipo' => 'R',
    ]);

    $this->get(route('marcaciones.index'))
        ->assertOk()
        ->assertSee('Diaz')
        ->assertSee('777');
});

test('el rango de fechas excluye lo que queda fuera', function () {
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '888', 'Paterno' => 'Vieja', 'Materno' => null, 'Nombres' => 'Marca', 'PinReloj' => null, 'MarcaDirecta' => false,
    ]);
    DB::connection('sia')->table('Asistencia')->insert([
        'IdPersona' => '888',
        'Fecha' => now()->subYears(2)->toDateTimeString(),
        'Hora' => now()->toDateTimeString(),
        'Tipo' => 'R',
    ]);

    // El rango por defecto arranca en el mes actual: la marcación de hace 2 años queda fuera.
    $this->get(route('marcaciones.index'))
        ->assertOk()
        ->assertSee('Sin marcaciones en el rango seleccionado');
});

test('un invitado no puede ver marcaciones', function () {
    auth()->logout();

    $this->get(route('marcaciones.index'))->assertRedirect();
});

test('un usuario sin permiso no puede ver marcaciones', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('marcaciones.index'))->assertForbidden();
});

test('busca marcaciones por apellido del funcionario', function () {
    DB::connection('sia')->table('Personas')->insert([
        ['IdPersona' => '1', 'Paterno' => 'Zabaleta', 'Materno' => null, 'Nombres' => 'Ana', 'PinReloj' => null, 'MarcaDirecta' => false],
        ['IdPersona' => '2', 'Paterno' => 'Quiroga', 'Materno' => null, 'Nombres' => 'Beto', 'PinReloj' => null, 'MarcaDirecta' => false],
    ]);
    DB::connection('sia')->table('Asistencia')->insert([
        ['IdPersona' => '1', 'Fecha' => now()->toDateString(), 'Hora' => now()->toDateTimeString(), 'Tipo' => 'R'],
        ['IdPersona' => '2', 'Fecha' => now()->toDateString(), 'Hora' => now()->toDateTimeString(), 'Tipo' => 'R'],
    ]);

    $this->get(route('marcaciones.index', ['buscar' => 'Zabaleta']))
        ->assertOk()
        ->assertSee('Zabaleta')
        ->assertDontSee('Quiroga');
});

test('busca marcaciones por CI del funcionario', function () {
    DB::connection('sia')->table('Personas')->insert([
        ['IdPersona' => '111', 'Paterno' => 'Rocabado', 'Materno' => null, 'Nombres' => 'Ana', 'PinReloj' => null, 'MarcaDirecta' => false],
        ['IdPersona' => '222', 'Paterno' => 'Salvatierra', 'Materno' => null, 'Nombres' => 'Beto', 'PinReloj' => null, 'MarcaDirecta' => false],
    ]);
    DB::connection('sia')->table('Asistencia')->insert([
        ['IdPersona' => '111', 'Fecha' => now()->toDateString(), 'Hora' => now()->toDateTimeString(), 'Tipo' => 'R'],
        ['IdPersona' => '222', 'Fecha' => now()->toDateString(), 'Hora' => now()->toDateTimeString(), 'Tipo' => 'R'],
    ]);

    $this->get(route('marcaciones.index', ['buscar' => '111']))
        ->assertOk()
        ->assertSee('Rocabado')
        ->assertDontSee('Salvatierra');
});

test('filtra por tipo de marcación', function () {
    DB::connection('sia')->table('Personas')->insert([
        ['IdPersona' => '1', 'Paterno' => 'Relojero', 'Materno' => null, 'Nombres' => 'Ana', 'PinReloj' => null, 'MarcaDirecta' => false],
        ['IdPersona' => '2', 'Paterno' => 'Manualino', 'Materno' => null, 'Nombres' => 'Beto', 'PinReloj' => null, 'MarcaDirecta' => false],
    ]);
    DB::connection('sia')->table('Asistencia')->insert([
        ['IdPersona' => '1', 'Fecha' => now()->toDateString(), 'Hora' => now()->toDateTimeString(), 'Tipo' => 'R'],
        ['IdPersona' => '2', 'Fecha' => now()->toDateString(), 'Hora' => now()->toDateTimeString(), 'Tipo' => 'M'],
    ]);

    $this->get(route('marcaciones.index', ['tipo' => 'R']))
        ->assertOk()
        ->assertSee('Relojero')
        ->assertDontSee('Manualino');
});

test('una marcación manual no se pinta con el color de reloj', function () {
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '333', 'Paterno' => 'Manual', 'Materno' => null, 'Nombres' => 'Uno', 'PinReloj' => null, 'MarcaDirecta' => false,
    ]);
    DB::connection('sia')->table('Asistencia')->insert([
        'IdPersona' => '333',
        'Fecha' => now()->toDateString(),
        'Hora' => now()->toDateTimeString(),
        'Tipo' => 'M',
    ]);

    // El CSS estático del layout siempre define .pill--ok (regla, no dato);
    // se verifica el <span> renderizado puntual, no una búsqueda de substring.
    $this->get(route('marcaciones.index'))
        ->assertOk()
        ->assertSee('<span class="pill pill--advertencia">M</span>', escape: false);
});
