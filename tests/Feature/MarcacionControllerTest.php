<?php

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
