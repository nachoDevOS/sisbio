<?php

use App\Models\Sia\Persona;
use App\Models\Sia\Profesion;
use App\Models\User;
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
        'MarcaDirecta' => false,
    ]);

    $this->get(route('funcionarios.index'))
        ->assertOk()
        ->assertSee('Perez')
        ->assertSee('12345678');
});

test('la búsqueda filtra por nombre', function () {
    DB::connection('sia')->table('Personas')->insert([
        ['IdPersona' => '1', 'Paterno' => 'Alfa', 'Materno' => null, 'Nombres' => 'Ana', 'PinReloj' => null, 'MarcaDirecta' => false],
        ['IdPersona' => '2', 'Paterno' => 'Beta', 'Materno' => null, 'Nombres' => 'Beto', 'PinReloj' => null, 'MarcaDirecta' => false],
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

test('muestra el formulario de alta', function () {
    Profesion::factory()->create(['NombreProfesion' => 'CONTADOR GENERAL']);

    $this->get(route('funcionarios.create'))
        ->assertOk()
        ->assertSee('Nuevo funcionario')
        ->assertSee('CONTADOR GENERAL');
});

test('registra un funcionario nuevo en el SIA', function () {
    $profesion = Profesion::factory()->create();

    $this->post(route('funcionarios.store'), [
        'IdPersona' => '1234567',
        'OrigenId' => 'BE',
        'Paterno' => 'Suárez',
        'Materno' => 'Roca',
        'Nombres' => 'Ana María',
        'FechaNacimiento' => '1990-05-10',
        'LugarNacimiento' => 'Trinidad',
        'Sexo' => 'F',
        'EstadoCivil' => 'S',
        'CodigoProfesion' => $profesion->CodigoProfesion,
        'NivelEstudio' => 'Profesional',
        'Telefono' => '71234567',
        'Direccion' => 'Av. 6 de Agosto 123',
        'CorreoE' => 'ana@example.com',
    ])
        ->assertRedirect(route('funcionarios.index'))
        ->assertSessionHas('estado');

    $persona = Persona::query()->find('1234567');

    expect($persona)->not->toBeNull()
        ->and($persona->Paterno)->toBe('Suárez')
        // Sección "Control de asistencia" deshabilitada: siempre entra sin
        // PIN y sin marcación con contraseña.
        ->and($persona->PinReloj)->toBeNull()
        ->and($persona->MarcaDirecta)->toBeFalse();
});

test('el alta valida obligatorios y carnet repetido', function () {
    Persona::factory()->create(['IdPersona' => '9999999']);

    $this->post(route('funcionarios.store'), [
        'IdPersona' => '9999999',
        'Paterno' => '',
        'Nombres' => '',
    ])->assertSessionHasErrors(['IdPersona', 'Paterno', 'Nombres', 'FechaNacimiento', 'Sexo', 'EstadoCivil', 'CodigoProfesion']);

    expect(Persona::query()->count())->toBe(1);
});

test('muestra la ficha de detalle con datos', function () {
    $profesion = Profesion::factory()->create(['NombreProfesion' => 'CONTADOR GENERAL']);
    $persona = Persona::factory()->create([
        'IdPersona' => '7778888',
        'Paterno' => 'Detalle',
        'Nombres' => 'Vista Completa',
        'CodigoProfesion' => $profesion->CodigoProfesion,
    ]);

    $this->get(route('funcionarios.show', $persona))
        ->assertOk()
        ->assertSee('Detalle')
        ->assertSee('Vista Completa')
        ->assertSee('CONTADOR GENERAL');
});

test('muestra el formulario de edición con los datos actuales', function () {
    Profesion::factory()->create();
    $persona = Persona::factory()->create(['Paterno' => 'Zabaleta']);

    $this->get(route('funcionarios.edit', $persona))
        ->assertOk()
        ->assertSee('Editar funcionario')
        ->assertSee('Zabaleta');
});

test('actualiza un funcionario sin tocar el carnet', function () {
    $profesion = Profesion::factory()->create();
    $persona = Persona::factory()->create([
        'IdPersona' => '5555555',
        'Paterno' => 'Original',
    ]);

    $this->put(route('funcionarios.update', $persona), [
        'Paterno' => 'Cambiado',
        'Nombres' => 'Nuevo Nombre',
        'FechaNacimiento' => '1985-01-20',
        'Sexo' => 'M',
        'EstadoCivil' => 'C',
        'CodigoProfesion' => $profesion->CodigoProfesion,
    ])
        ->assertRedirect(route('funcionarios.index'))
        ->assertSessionHas('estado');

    $persona->refresh();

    expect($persona->Paterno)->toBe('Cambiado')
        ->and($persona->Nombres)->toBe('Nuevo Nombre')
        ->and(trim($persona->IdPersona))->toBe('5555555');
});

test('un usuario sin permiso no puede entrar al listado', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('funcionarios.index'))->assertForbidden();
});
