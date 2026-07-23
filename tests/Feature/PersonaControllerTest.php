<?php

use App\Models\Sia\Asistencia;
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

test('la búsqueda por varias palabras cruza nombre y apellido', function () {
    DB::connection('sia')->table('Personas')->insert([
        ['IdPersona' => '10', 'Paterno' => 'Molina', 'Materno' => 'Guzman', 'Nombres' => 'Ignacio', 'PinReloj' => null, 'MarcaDirecta' => false],
        ['IdPersona' => '20', 'Paterno' => 'Perez', 'Materno' => 'Rojas', 'Nombres' => 'Ignacio', 'PinReloj' => null, 'MarcaDirecta' => false],
    ]);

    // "ignacio m" debe encontrar a Ignacio Molina (Nombres + Paterno en
    // columnas distintas) y dejar fuera a Ignacio Perez.
    $this->get(route('funcionarios.index', ['q' => 'ignacio m']))
        ->assertOk()
        ->assertSee('Molina')
        ->assertDontSee('Perez');
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

test('la ficha muestra las marcaciones del funcionario dentro del rango por defecto', function () {
    $persona = Persona::factory()->create(['IdPersona' => '7778888']);

    Asistencia::factory()->create([
        'IdPersona' => $persona->IdPersona,
        'Fecha' => today(),
        'Hora' => '1899-12-30 08:15:00',
        'Tipo' => Asistencia::TIPO_RELOJ,
    ]);

    $this->get(route('funcionarios.show', $persona))
        ->assertOk()
        ->assertSee('08:15:00');
});

test('la ficha no mezcla marcaciones de otro funcionario', function () {
    $persona = Persona::factory()->create(['IdPersona' => '7778888']);
    $otro = Persona::factory()->create(['IdPersona' => '1112222']);

    Asistencia::factory()->create(['IdPersona' => $persona->IdPersona, 'Fecha' => today(), 'Hora' => '1899-12-30 08:00:00']);
    Asistencia::factory()->create(['IdPersona' => $otro->IdPersona, 'Fecha' => today(), 'Hora' => '1899-12-30 09:00:00']);

    $this->get(route('funcionarios.show', $persona))
        ->assertOk()
        ->assertSee('08:00:00')
        ->assertDontSee('09:00:00');
});

test('la ficha filtra las marcaciones por rango de fechas y tipo', function () {
    $persona = Persona::factory()->create(['IdPersona' => '7778888']);

    Asistencia::factory()->create([
        'IdPersona' => $persona->IdPersona,
        'Fecha' => today()->subMonths(3),
        'Hora' => '1899-12-30 07:00:00',
        'Tipo' => Asistencia::TIPO_MANUAL,
    ]);
    Asistencia::factory()->create([
        'IdPersona' => $persona->IdPersona,
        'Fecha' => today(),
        'Hora' => '1899-12-30 08:00:00',
        'Tipo' => Asistencia::TIPO_RELOJ,
    ]);

    $this->get(route('funcionarios.show', $persona))
        ->assertOk()
        ->assertSee('08:00:00')
        ->assertDontSee('07:00:00');

    $this->get(route('funcionarios.show', [
        'persona' => $persona,
        'desde' => today()->subMonths(4)->toDateString(),
        'hasta' => today()->toDateString(),
        'tipo' => Asistencia::TIPO_MANUAL,
    ]))
        ->assertOk()
        ->assertSee('07:00:00')
        ->assertDontSee('08:00:00');
});

test('el reporte imprimible lista las marcaciones crudas del rango', function () {
    $persona = Persona::factory()->create([
        'IdPersona' => '7633685',
        'Paterno' => 'Molina',
        'Materno' => 'Guzman',
        'Nombres' => 'Ignacio',
        'PinReloj' => '7633685',
    ]);

    Asistencia::factory()->create([
        'IdPersona' => $persona->IdPersona,
        'Fecha' => today(),
        'Hora' => '1899-12-30 08:15:00',
        'Tipo' => Asistencia::TIPO_RELOJ,
    ]);
    Asistencia::factory()->create([
        'IdPersona' => $persona->IdPersona,
        'Fecha' => today()->subYear(),
        'Hora' => '1899-12-30 07:00:00',
        'Tipo' => Asistencia::TIPO_RELOJ,
    ]);

    $this->get(route('funcionarios.reporte', [
        'persona' => $persona,
        'desde' => today()->startOfMonth()->toDateString(),
        'hasta' => today()->toDateString(),
    ]))
        ->assertOk()
        ->assertSee('REPORTE DE MARCACIONES')
        ->assertSee('GOBIERNO AUTONOMO DEPARTAMENTAL DEL BENI')
        ->assertSee('Molina Guzman Ignacio')
        ->assertSeeText('PIN Reloj: 7633685')
        ->assertSee('08:15:00')
        ->assertDontSee('07:00:00')
        ->assertSee('Total registros:')
        ->assertSee('descarga directa desde reloj');
});
