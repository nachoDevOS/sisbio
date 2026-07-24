<?php

use App\Models\Asistencia;
use App\Models\Persona;
use App\Models\Profesion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

test('el listado muestra los funcionarios', function () {
    Persona::factory()->create([
        'ci' => '12345678',
        'paterno' => 'Perez',
        'materno' => 'Gomez',
        'nombres' => 'Juan',
    ]);

    $this->get(route('funcionarios.index'))
        ->assertOk()
        ->assertSee('Perez')
        ->assertSee('12345678');
});

test('la búsqueda filtra por nombre', function () {
    Persona::factory()->create(['ci' => '1', 'paterno' => 'Alfa', 'nombres' => 'Ana']);
    Persona::factory()->create(['ci' => '2', 'paterno' => 'Beta', 'nombres' => 'Beto']);

    $this->get(route('funcionarios.index', ['q' => 'Alfa']))
        ->assertOk()
        ->assertSee('Alfa')
        ->assertDontSee('Beta');
});

test('la búsqueda por varias palabras cruza nombre y apellido', function () {
    Persona::factory()->create(['ci' => '10', 'paterno' => 'Molina', 'materno' => 'Guzman', 'nombres' => 'Ignacio']);
    Persona::factory()->create(['ci' => '20', 'paterno' => 'Perez', 'materno' => 'Rojas', 'nombres' => 'Ignacio']);

    // "ignacio m" debe encontrar a Ignacio Molina (nombres + paterno en
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
    Profesion::factory()->create(['nombreProfesion' => 'CONTADOR GENERAL']);

    $this->get(route('funcionarios.create'))
        ->assertOk()
        ->assertSee('Nuevo funcionario')
        ->assertSee('CONTADOR GENERAL');
});

test('registra un funcionario nuevo', function () {
    $profesion = Profesion::factory()->create();

    $this->post(route('funcionarios.store'), [
        'ci' => '1234567',
        'origenId' => 'BE',
        'paterno' => 'Suárez',
        'materno' => 'Roca',
        'nombres' => 'Ana María',
        'fechaNacimiento' => '1990-05-10',
        'lugarNacimiento' => 'Trinidad',
        'sexo' => 'F',
        'estadoCivil' => 'S',
        'codigoProfesion' => $profesion->codigoProfesion,
        'nivelEstudio' => 'Profesional',
        'telefono' => '71234567',
        'direccion' => 'Av. 6 de Agosto 123',
        'correo' => 'ana@example.com',
    ])
        ->assertRedirect(route('funcionarios.index'))
        ->assertSessionHas('estado');

    $persona = Persona::query()->where('ci', '1234567')->first();

    expect($persona)->not->toBeNull()
        ->and($persona->paterno)->toBe('Suárez')
        // Sección "Control de asistencia" deshabilitada: siempre entra sin
        // PIN y sin marcación con contraseña.
        ->and($persona->pinReloj)->toBeNull()
        ->and($persona->marcaDirecta)->toBeFalse();
});

test('el alta valida obligatorios y carnet repetido', function () {
    Persona::factory()->create(['ci' => '9999999']);

    $this->post(route('funcionarios.store'), [
        'ci' => '9999999',
        'paterno' => '',
        'nombres' => '',
    ])->assertSessionHasErrors(['ci', 'paterno', 'nombres', 'fechaNacimiento', 'sexo', 'estadoCivil', 'codigoProfesion']);

    expect(Persona::query()->count())->toBe(1);
});

test('muestra la ficha de detalle con datos', function () {
    $profesion = Profesion::factory()->create(['nombreProfesion' => 'CONTADOR GENERAL']);
    $persona = Persona::factory()->create([
        'ci' => '7778888',
        'paterno' => 'Detalle',
        'nombres' => 'Vista Completa',
        'codigoProfesion' => $profesion->codigoProfesion,
    ]);

    $this->get(route('funcionarios.show', $persona))
        ->assertOk()
        ->assertSee('Detalle')
        ->assertSee('Vista Completa')
        ->assertSee('CONTADOR GENERAL');
});

test('muestra el formulario de edición con los datos actuales', function () {
    Profesion::factory()->create();
    $persona = Persona::factory()->create(['paterno' => 'Zabaleta']);

    $this->get(route('funcionarios.edit', $persona))
        ->assertOk()
        ->assertSee('Editar funcionario')
        ->assertSee('Zabaleta');
});

test('actualiza un funcionario sin tocar el carnet', function () {
    $profesion = Profesion::factory()->create();
    $persona = Persona::factory()->create([
        'ci' => '5555555',
        'paterno' => 'Original',
    ]);

    $this->put(route('funcionarios.update', $persona), [
        'paterno' => 'Cambiado',
        'nombres' => 'Nuevo Nombre',
        'fechaNacimiento' => '1985-01-20',
        'sexo' => 'M',
        'estadoCivil' => 'C',
        'codigoProfesion' => $profesion->codigoProfesion,
    ])
        ->assertRedirect(route('funcionarios.index'))
        ->assertSessionHas('estado');

    $persona->refresh();

    expect($persona->paterno)->toBe('Cambiado')
        ->and($persona->nombres)->toBe('Nuevo Nombre')
        ->and(trim($persona->ci))->toBe('5555555');
});

test('un usuario sin permiso no puede entrar al listado', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('funcionarios.index'))->assertForbidden();
});

test('la ficha muestra las marcaciones del funcionario dentro del rango por defecto', function () {
    $persona = Persona::factory()->create(['ci' => '7778888']);

    Asistencia::factory()->create([
        'ci' => $persona->ci,
        'fecha' => today(),
        'hora' => '1899-12-30 08:15:00',
        'tipo' => Asistencia::TIPO_RELOJ,
    ]);

    $this->get(route('funcionarios.show', $persona))
        ->assertOk()
        ->assertSee('08:15:00');
});

test('la ficha no mezcla marcaciones de otro funcionario', function () {
    $persona = Persona::factory()->create(['ci' => '7778888']);
    $otro = Persona::factory()->create(['ci' => '1112222']);

    Asistencia::factory()->create(['ci' => $persona->ci, 'fecha' => today(), 'hora' => '1899-12-30 08:00:00']);
    Asistencia::factory()->create(['ci' => $otro->ci, 'fecha' => today(), 'hora' => '1899-12-30 09:00:00']);

    $this->get(route('funcionarios.show', $persona))
        ->assertOk()
        ->assertSee('08:00:00')
        ->assertDontSee('09:00:00');
});

test('la ficha filtra las marcaciones por rango de fechas y tipo', function () {
    $persona = Persona::factory()->create(['ci' => '7778888']);

    Asistencia::factory()->create([
        'ci' => $persona->ci,
        'fecha' => today()->subMonths(3),
        'hora' => '1899-12-30 07:00:00',
        'tipo' => Asistencia::TIPO_MANUAL,
    ]);
    Asistencia::factory()->create([
        'ci' => $persona->ci,
        'fecha' => today(),
        'hora' => '1899-12-30 08:00:00',
        'tipo' => Asistencia::TIPO_RELOJ,
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
        'ci' => '7633685',
        'paterno' => 'Molina',
        'materno' => 'Guzman',
        'nombres' => 'Ignacio',
        'pinReloj' => '7633685',
    ]);

    Asistencia::factory()->create([
        'ci' => $persona->ci,
        'fecha' => today(),
        'hora' => '1899-12-30 08:15:00',
        'tipo' => Asistencia::TIPO_RELOJ,
    ]);
    Asistencia::factory()->create([
        'ci' => $persona->ci,
        'fecha' => today()->subYear(),
        'hora' => '1899-12-30 07:00:00',
        'tipo' => Asistencia::TIPO_RELOJ,
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
