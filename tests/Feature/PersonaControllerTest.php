<?php

use App\Models\Asistencia;
use App\Models\Persona;
use App\Models\Profesion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

test('el listado SIAT muestra los funcionarios locales', function () {
    Persona::factory()->create([
        'ci' => '12345678',
        'paterno' => 'Perez',
        'materno' => 'Gomez',
        'nombres' => 'Juan',
    ]);

    $this->get(route('funcionarios.index', ['fuente' => 'siat']))
        ->assertOk()
        ->assertSee('Perez')
        ->assertSee('12345678');
});

test('la búsqueda SIAT filtra por nombre', function () {
    Persona::factory()->create(['ci' => '1', 'paterno' => 'Alfa', 'nombres' => 'Ana']);
    Persona::factory()->create(['ci' => '2', 'paterno' => 'Beta', 'nombres' => 'Beto']);

    $this->get(route('funcionarios.index', ['fuente' => 'siat', 'q' => 'Alfa']))
        ->assertOk()
        ->assertSee('Alfa')
        ->assertDontSee('Beta');
});

test('la búsqueda SIAT por varias palabras cruza nombre y apellido', function () {
    Persona::factory()->create(['ci' => '10', 'paterno' => 'Molina', 'materno' => 'Guzman', 'nombres' => 'Ignacio']);
    Persona::factory()->create(['ci' => '20', 'paterno' => 'Perez', 'materno' => 'Rojas', 'nombres' => 'Ignacio']);

    // "ignacio m" debe encontrar a Ignacio Molina (nombres + paterno en
    // columnas distintas) y dejar fuera a Ignacio Perez.
    $this->get(route('funcionarios.index', ['fuente' => 'siat', 'q' => 'ignacio m']))
        ->assertOk()
        ->assertSee('Molina')
        ->assertDontSee('Perez');
});

test('el listado por defecto usa Mamoré y muestra sus personas', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake([
        'mamore.test/api/personal/people*' => Http::response([
            'data' => [
                ['id' => 25, 'ci' => '7654321', 'paternal_surname' => 'Perez', 'maternal_surname' => 'Gomez', 'first_name' => 'Juan', 'middle_name' => 'Carlos'],
            ],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 10, 'total' => 1],
        ], 200),
    ]);

    $this->get(route('funcionarios.index'))
        ->assertOk()
        ->assertSee('Perez')
        ->assertSee('7654321')
        ->assertSee('Juan Carlos');

    Http::assertSent(fn ($request) => $request->hasHeader('X-API-KEY', 'secreta')
        && str_contains($request->url(), '/people'));
});

test('la búsqueda Mamoré por varias palabras filtra localmente (nombre + apellido)', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake([
        'mamore.test/api/personal/people*' => Http::response([
            'data' => [
                ['id' => 1, 'ci' => '111', 'full_name' => 'SERGIO MILTON MORALES FLORES'],
                ['id' => 2, 'ci' => '222', 'full_name' => 'JUANA MORALES PEREZ'],
            ],
            'meta' => ['total' => 2, 'per_page' => 10, 'current_page' => 1],
        ], 200),
    ]);

    $this->get(route('funcionarios.index', ['q' => 'milton morales']))
        ->assertOk()
        ->assertSee('SERGIO MILTON MORALES FLORES')
        ->assertDontSee('JUANA MORALES PEREZ');
});

test('la fuente Mamoré avisa si la API responde con error', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake(['mamore.test/*' => Http::response(['message' => 'no'], 401)]);

    $this->get(route('funcionarios.index'))
        ->assertOk()
        ->assertSee('La clave de la API de Mamoré es inválida');
});

test('la fuente Mamoré avisa si no está configurada', function () {
    config()->set('services.mamore.url', null);
    config()->set('services.mamore.key', null);

    $this->get(route('funcionarios.index'))
        ->assertOk()
        ->assertSee('no está configurada');
});

test('la ficha de una persona de Mamoré se ve por cédula', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake([
        'mamore.test/api/personal/people/ci/*' => Http::response([
            'data' => [
                'id' => 25, 'full_name' => 'Juan Carlos Perez Gomez', 'ci' => '7654321',
                'full_ci' => '7654321-BE', 'phone' => '70000000', 'email' => 'juan@example.com',
            ],
        ], 200),
    ]);

    $this->get(route('funcionarios.mamore', ['ci' => '7654321']))
        ->assertOk()
        ->assertSee('Juan Carlos Perez Gomez')
        ->assertSee('7654321-BE');
});

test('la ficha de Mamoré da 404 si la cédula no existe', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake(['mamore.test/*' => Http::response(['message' => 'not found'], 404)]);

    $this->get(route('funcionarios.mamore', ['ci' => '000']))->assertNotFound();
});

test('un invitado no puede ver funcionarios', function () {
    auth()->logout();

    $this->get(route('funcionarios.index'))->assertRedirect();
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
