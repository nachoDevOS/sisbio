<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    fakeSiaDatabase();
    $this->actingAs(asSuperAdmin());
});

test('el listado muestra los funcionarios del SIA', function () {
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '12345678',
        'Paterno' => 'Perez',
        'Materno' => 'Gomez',
        'Nombres' => 'Juan',
        'PinReloj' => '99',
    ]);

    $this->get(route('funcionarios.index'))
        ->assertOk()
        ->assertSee('Perez')
        ->assertSee('12345678');
});

test('la búsqueda filtra por nombre', function () {
    DB::connection('sia')->table('Personas')->insert([
        ['IdPersona' => '1', 'Paterno' => 'Alfa', 'Materno' => null, 'Nombres' => 'Ana', 'PinReloj' => null],
        ['IdPersona' => '2', 'Paterno' => 'Beta', 'Materno' => null, 'Nombres' => 'Beto', 'PinReloj' => null],
    ]);

    $this->get(route('funcionarios.index', ['q' => 'Alfa']))
        ->assertOk()
        ->assertSee('Alfa')
        ->assertDontSee('Beta');
});

test('un invitado no puede ver funcionarios', function () {
    auth()->logout();

    $this->get(route('funcionarios.index'))->assertRedirect();
});
