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

    $this->get(route('funcionarios.list', ['fuente' => 'siat']))
        ->assertOk()
        ->assertSee('Perez')
        ->assertSee('12345678');
});

test('la búsqueda SIAT filtra por nombre', function () {
    Persona::factory()->create(['ci' => '1', 'paterno' => 'Alfa', 'nombres' => 'Ana']);
    Persona::factory()->create(['ci' => '2', 'paterno' => 'Beta', 'nombres' => 'Beto']);

    $this->get(route('funcionarios.list', ['fuente' => 'siat', 'q' => 'Alfa']))
        ->assertOk()
        ->assertSee('Alfa')
        ->assertDontSee('Beta');
});

test('la búsqueda SIAT por varias palabras cruza nombre y apellido', function () {
    Persona::factory()->create(['ci' => '10', 'paterno' => 'Molina', 'materno' => 'Guzman', 'nombres' => 'Ignacio']);
    Persona::factory()->create(['ci' => '20', 'paterno' => 'Perez', 'materno' => 'Rojas', 'nombres' => 'Ignacio']);

    // "ignacio m" debe encontrar a Ignacio Molina (nombres + paterno en
    // columnas distintas) y dejar fuera a Ignacio Perez.
    $this->get(route('funcionarios.list', ['fuente' => 'siat', 'q' => 'ignacio m']))
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

    $this->get(route('funcionarios.list'))
        ->assertOk()
        ->assertSee('Perez')
        ->assertSee('7654321')
        ->assertSee('Juan Carlos');

    Http::assertSent(fn ($request) => $request->hasHeader('X-API-KEY', 'secreta')
        && str_contains($request->url(), '/people'));
});

test('el filtro «sin contrato» le pide a la API el listado de personas sin contrato', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake([
        'mamore.test/api/personal/people*' => Http::response([
            'data' => [
                ['id' => 9, 'ci' => '999', 'full_name' => 'PERSONA SIN CONTRATO', 'has_contract' => false],
            ],
            'meta' => ['current_page' => 1, 'per_page' => 10, 'total' => 1, 'contrato' => 'sin'],
        ], 200),
    ]);

    $this->get(route('funcionarios.list', ['contrato' => 'sin']))
        ->assertOk()
        ->assertSee('PERSONA SIN CONTRATO')
        // Sin contrato no hay cargo ni dirección que mostrar.
        ->assertSee('<td>—</td>', false);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/people')
        && str_contains($request->url(), 'contrato=sin'));
});

test('el listado publica los totales por situación de contrato para el select', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake([
        'mamore.test/api/personal/people*' => Http::response([
            'data' => [['id' => 1, 'ci' => '111', 'full_name' => 'CUALQUIERA', 'has_contract' => true]],
            'meta' => [
                'current_page' => 1, 'per_page' => 10, 'total' => 4595,
                'total_con_contrato' => 1040, 'total_sin_contrato' => 3555,
            ],
        ], 200),
    ]);

    $this->get(route('funcionarios.list'))
        ->assertOk()
        ->assertSee('data-con="1040"', false)
        ->assertSee('data-sin="3555"', false)
        // «Todos» es la suma de las dos situaciones.
        ->assertSee('data-todos="4595"', false);
});

test('la lista muestra juntas a las personas con contrato y sin contrato', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    // Un único listado: la persona con contrato lo trae embebido, la que no
    // tiene viene con `contrato: null` y aparece igual en la tabla.
    Http::fake([
        'mamore.test/api/personal/people*' => Http::response([
            'data' => [
                [
                    'id' => 4772,
                    'ci' => '7604314',
                    'full_name' => 'LUIS CARLOS ALPIRE DURAN',
                    'has_contract' => true,
                    'contrato' => [
                        'id' => 16437,
                        'code' => 'SDAF-132/2026',
                        'cargo_completo' => 'APOYO ADMINISTRATIVO - (Analista II)',
                        'direccion_administrativa' => ['id' => 16, 'nombre' => 'Secretaria Departamental de Administracion y Finanzas', 'sigla' => 'SDAF'],
                    ],
                ],
                [
                    'id' => 500,
                    'ci' => '1938650',
                    'full_name' => 'CLAUDIA VARGAS',
                    'has_contract' => false,
                    'contrato' => null,
                ],
            ],
            'meta' => ['current_page' => 1, 'per_page' => 10, 'total' => 2, 'total_con_contrato' => 1, 'total_sin_contrato' => 1],
        ], 200),
    ]);

    $this->get(route('funcionarios.list'))
        ->assertOk()
        // La que tiene contrato, con su cargo y su dirección.
        ->assertSee('LUIS CARLOS ALPIRE DURAN')
        ->assertSee('APOYO ADMINISTRATIVO - (Analista II)')
        ->assertSee('SDAF')
        ->assertSee('Con contrato')
        // La que no tiene, igual en la lista.
        ->assertSee('CLAUDIA VARGAS')
        ->assertSee('Sin contrato');

    // Una sola petición, al único listado de la API.
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => str_contains($request->url(), '/people'));
});

test('el filtro «con contrato» le pide a la API solo los que tienen contrato', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake([
        'mamore.test/api/personal/people*' => Http::response([
            'data' => [
                [
                    'id' => 4772,
                    'ci' => '7604314',
                    'full_name' => 'LUIS CARLOS ALPIRE DURAN',
                    'has_contract' => true,
                    'contrato' => [
                        'cargo_completo' => 'APOYO ADMINISTRATIVO - (Analista II)',
                        'direccion_administrativa' => ['sigla' => 'SDAF'],
                    ],
                ],
            ],
            'meta' => ['current_page' => 1, 'per_page' => 10, 'total' => 1040, 'total_con_contrato' => 1040, 'total_sin_contrato' => 3555],
        ], 200),
    ]);

    $this->get(route('funcionarios.list', ['contrato' => 'con']))
        ->assertOk()
        ->assertSee('LUIS CARLOS ALPIRE DURAN')
        ->assertSee('APOYO ADMINISTRATIVO - (Analista II)')
        ->assertSee('SDAF');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/people')
        && str_contains($request->url(), 'contrato=con'));
});

test('el filtro por contrato no aplica a la fuente SIAT', function () {
    Persona::factory()->create(['ci' => '333', 'paterno' => 'Local', 'nombres' => 'Sin Contratos']);

    Http::fake();

    $this->get(route('funcionarios.list', ['fuente' => 'siat', 'contrato' => 'con']))
        ->assertOk()
        ->assertSee('Local');

    Http::assertNothingSent();
});

test('un valor raro en el filtro de contrato cae en «todos»', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake([
        'mamore.test/api/personal/people*' => Http::response([
            'data' => [['id' => 1, 'ci' => '111', 'full_name' => 'CUALQUIERA']],
            'meta' => ['current_page' => 1, 'per_page' => 10, 'total' => 1],
        ], 200),
    ]);

    $this->get(route('funcionarios.list', ['contrato' => 'inventado']))->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/people')
        && ! str_contains($request->url(), 'contrato='));
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

    $this->get(route('funcionarios.list', ['q' => 'milton morales']))
        ->assertOk()
        ->assertSee('SERGIO MILTON MORALES FLORES')
        ->assertDontSee('JUANA MORALES PEREZ');
});

test('la fuente Mamoré avisa si la API responde con error', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake(['mamore.test/*' => Http::response(['message' => 'no'], 401)]);

    $this->get(route('funcionarios.list'))
        ->assertOk()
        ->assertSee('La clave de la API de Mamoré es inválida');
});

test('la fuente Mamoré avisa si no está configurada', function () {
    config()->set('services.mamore.url', null);
    config()->set('services.mamore.key', null);

    $this->get(route('funcionarios.list'))
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

test('la ficha de Mamoré muestra el contrato vigente del funcionario', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake([
        'mamore.test/api/personal/people/ci/*' => Http::response([
            'data' => [
                'id' => 4772, 'full_name' => 'LUIS CARLOS ALPIRE DURAN', 'ci' => '7604314',
                'has_contract' => true,
                'contrato' => [
                    'code' => 'SDAF-132/2026',
                    'denominacion' => 'APOYO ADMINISTRATIVO',
                    'cargo_completo' => 'APOYO ADMINISTRATIVO - (Analista II)',
                    'direccion_administrativa' => ['nombre' => 'Secretaria Departamental de Administracion y Finanzas', 'sigla' => 'SDAF'],
                    'unidad_administrativa' => ['nombre' => 'DIRECCIÓN DPTAL. DE RECURSOS HUMANOS', 'sigla' => 'DRRHH'],
                    'procedure_type' => 'eventual',
                    'start' => '2026-07-14',
                    'finish' => '2026-12-31',
                ],
            ],
        ], 200),
    ]);

    $this->get(route('funcionarios.mamore', ['ci' => '7604314']))
        ->assertOk()
        ->assertSee('Contrato vigente')
        ->assertSee('APOYO ADMINISTRATIVO - (Analista II)')
        ->assertSee('DRRHH')
        ->assertSee('Con contrato');
});

test('la ficha de Mamoré avisa cuando la persona no tiene contrato', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake([
        'mamore.test/api/personal/people/ci/*' => Http::response([
            'data' => ['id' => 500, 'full_name' => 'CLAUDIA VARGAS', 'ci' => '1938650', 'has_contract' => false, 'contrato' => null],
        ], 200),
    ]);

    $this->get(route('funcionarios.mamore', ['ci' => '1938650']))
        ->assertOk()
        ->assertSee('CLAUDIA VARGAS')
        ->assertSee('Sin contrato')
        ->assertSee('no tiene un contrato firmado en Mamoré')
        ->assertDontSee('Contrato vigente');
});

test('la ficha de Mamoré trae el panel AJAX de marcaciones con esa cédula', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake([
        'mamore.test/api/personal/people/ci/*' => Http::response([
            'data' => ['id' => 25, 'full_name' => 'IGNACIO MOLINA GUZMAN', 'ci' => '7633685'],
        ], 200),
    ]);

    $this->get(route('funcionarios.mamore', ['ci' => '7633685']))
        ->assertOk()
        ->assertSee('Marcaciones')
        ->assertSee('id="m-results"', false)
        ->assertSee('const ci = "7633685"', false);
});

test('la ficha de Mamoré ofrece el reporte imprimible solo si la cédula está en la base local', function () {
    config()->set('services.mamore.url', 'http://mamore.test/api/personal');
    config()->set('services.mamore.key', 'secreta');

    Http::fake([
        'mamore.test/api/personal/people/ci/*' => Http::response([
            'data' => ['id' => 25, 'full_name' => 'IGNACIO MOLINA GUZMAN', 'ci' => '7633685'],
        ], 200),
    ]);

    $this->get(route('funcionarios.mamore', ['ci' => '7633685']))
        ->assertOk()
        ->assertDontSee('Imprimir reporte');

    Persona::factory()->create(['ci' => '7633685']);

    $this->get(route('funcionarios.mamore', ['ci' => '7633685']))
        ->assertOk()
        ->assertSee('Imprimir reporte');
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

test('la pantalla de funcionarios carga el shell del listado', function () {
    $this->get(route('funcionarios.index'))
        ->assertOk()
        ->assertSee('Funcionarios')
        ->assertSee('id="div-results"', false);
});

test('un usuario sin permiso no puede pedir el listado AJAX', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('funcionarios.list'))->assertForbidden();
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

test('la ficha trae el panel AJAX de marcaciones del funcionario', function () {
    $persona = Persona::factory()->create(['ci' => '7778888']);

    $this->get(route('funcionarios.show', $persona))
        ->assertOk()
        ->assertSee('Marcaciones')
        ->assertSee('id="m-results"', false)
        ->assertSee('const ci = "7778888"', false)
        ->assertSee('Imprimir reporte');
});

test('el listado AJAX muestra las marcaciones de la cédula dentro del rango por defecto', function () {
    Asistencia::factory()->create([
        'ci' => '7778888',
        'fecha' => today(),
        'hora' => '1899-12-30 08:15:00',
        'tipo' => Asistencia::TIPO_RELOJ,
    ]);

    $this->get(route('funcionarios.marcaciones.list', ['ci' => '7778888']))
        ->assertOk()
        ->assertSee('08:15:00');
});

test('el listado AJAX no mezcla marcaciones de otra cédula', function () {
    Asistencia::factory()->create(['ci' => '7778888', 'fecha' => today(), 'hora' => '1899-12-30 08:00:00']);
    Asistencia::factory()->create(['ci' => '1112222', 'fecha' => today(), 'hora' => '1899-12-30 09:00:00']);

    $this->get(route('funcionarios.marcaciones.list', ['ci' => '7778888']))
        ->assertOk()
        ->assertSee('08:00:00')
        ->assertDontSee('09:00:00');
});

test('el listado AJAX de marcaciones filtra por rango de fechas y tipo', function () {
    Asistencia::factory()->create([
        'ci' => '7778888',
        'fecha' => today()->subMonths(3),
        'hora' => '1899-12-30 07:00:00',
        'tipo' => Asistencia::TIPO_MANUAL,
    ]);
    Asistencia::factory()->create([
        'ci' => '7778888',
        'fecha' => today(),
        'hora' => '1899-12-30 08:00:00',
        'tipo' => Asistencia::TIPO_RELOJ,
    ]);

    $this->get(route('funcionarios.marcaciones.list', ['ci' => '7778888']))
        ->assertOk()
        ->assertSee('08:00:00')
        ->assertDontSee('07:00:00');

    $this->get(route('funcionarios.marcaciones.list', [
        'ci' => '7778888',
        'desde' => today()->subMonths(4)->toDateString(),
        'hasta' => today()->toDateString(),
        'tipo' => Asistencia::TIPO_MANUAL,
    ]))
        ->assertOk()
        ->assertSee('07:00:00')
        ->assertDontSee('08:00:00');
});

test('el listado AJAX de marcaciones respeta el selector de registros por página', function () {
    Asistencia::factory()->count(12)->create(['ci' => '7778888', 'fecha' => today()]);

    $this->get(route('funcionarios.marcaciones.list', ['ci' => '7778888', 'por_pagina' => 10]))
        ->assertOk()
        ->assertViewHas('marcaciones', fn ($marcaciones): bool => $marcaciones->count() === 10);
});

test('un usuario sin permiso no puede pedir las marcaciones por AJAX', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('funcionarios.marcaciones.list', ['ci' => '7778888']))->assertForbidden();
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
