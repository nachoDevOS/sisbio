<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    fakeSiaDatabase();
    $this->actingAs(asSuperAdmin());
});

test('el listado muestra las marcaciones del rango por defecto', function () {
    DB::table('personas')->insert([
        'ci' => '777', 'paterno' => 'Diaz', 'materno' => null, 'nombres' => 'Eva', 'pinReloj' => null, 'marcaDirecta' => false,
    ]);
    DB::table('asistencias')->insert([
        'ci' => '777',
        'fecha' => now()->startOfDay()->toDateTimeString(),
        'hora' => now()->toDateTimeString(),
        'tipo' => 'R',
    ]);

    $this->get(route('marcaciones.index'))
        ->assertOk()
        ->assertSee('Diaz')
        ->assertSee('777');
});

test('el rango de fechas excluye lo que queda fuera', function () {
    DB::table('personas')->insert([
        'ci' => '888', 'paterno' => 'Vieja', 'materno' => null, 'nombres' => 'Marca', 'pinReloj' => null, 'marcaDirecta' => false,
    ]);
    DB::table('asistencias')->insert([
        'ci' => '888',
        'fecha' => now()->subYears(2)->toDateTimeString(),
        'hora' => now()->toDateTimeString(),
        'tipo' => 'R',
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
    DB::table('personas')->insert([
        ['ci' => '1', 'paterno' => 'Zabaleta', 'materno' => null, 'nombres' => 'Ana', 'pinReloj' => null, 'marcaDirecta' => false],
        ['ci' => '2', 'paterno' => 'Quiroga', 'materno' => null, 'nombres' => 'Beto', 'pinReloj' => null, 'marcaDirecta' => false],
    ]);
    DB::table('asistencias')->insert([
        ['ci' => '1', 'fecha' => now()->toDateString(), 'hora' => now()->toDateTimeString(), 'tipo' => 'R'],
        ['ci' => '2', 'fecha' => now()->toDateString(), 'hora' => now()->toDateTimeString(), 'tipo' => 'R'],
    ]);

    $this->get(route('marcaciones.index', ['buscar' => 'Zabaleta']))
        ->assertOk()
        ->assertSee('Zabaleta')
        ->assertDontSee('Quiroga');
});

test('busca marcaciones por nombre y apellido combinados', function () {
    DB::table('personas')->insert([
        ['ci' => '1', 'paterno' => 'Molina', 'materno' => 'Guzman', 'nombres' => 'Ignacio', 'pinReloj' => null, 'marcaDirecta' => false],
        ['ci' => '2', 'paterno' => 'Perez', 'materno' => 'Rojas', 'nombres' => 'Ignacio', 'pinReloj' => null, 'marcaDirecta' => false],
    ]);
    DB::table('asistencias')->insert([
        ['ci' => '1', 'fecha' => now()->toDateString(), 'hora' => now()->toDateTimeString(), 'tipo' => 'R'],
        ['ci' => '2', 'fecha' => now()->toDateString(), 'hora' => now()->toDateTimeString(), 'tipo' => 'R'],
    ]);

    // "ignacio m" cruza nombres + paterno: encuentra a Ignacio Molina y deja
    // fuera a Ignacio Perez.
    $this->get(route('marcaciones.index', ['buscar' => 'ignacio m']))
        ->assertOk()
        ->assertSee('Molina')
        ->assertDontSee('Perez');
});

test('busca marcaciones por CI del funcionario', function () {
    DB::table('personas')->insert([
        ['ci' => '111', 'paterno' => 'Rocabado', 'materno' => null, 'nombres' => 'Ana', 'pinReloj' => null, 'marcaDirecta' => false],
        ['ci' => '222', 'paterno' => 'Salvatierra', 'materno' => null, 'nombres' => 'Beto', 'pinReloj' => null, 'marcaDirecta' => false],
    ]);
    DB::table('asistencias')->insert([
        ['ci' => '111', 'fecha' => now()->toDateString(), 'hora' => now()->toDateTimeString(), 'tipo' => 'R'],
        ['ci' => '222', 'fecha' => now()->toDateString(), 'hora' => now()->toDateTimeString(), 'tipo' => 'R'],
    ]);

    $this->get(route('marcaciones.index', ['buscar' => '111']))
        ->assertOk()
        ->assertSee('Rocabado')
        ->assertDontSee('Salvatierra');
});

test('filtra por tipo de marcación', function () {
    DB::table('personas')->insert([
        ['ci' => '1', 'paterno' => 'Relojero', 'materno' => null, 'nombres' => 'Ana', 'pinReloj' => null, 'marcaDirecta' => false],
        ['ci' => '2', 'paterno' => 'Manualino', 'materno' => null, 'nombres' => 'Beto', 'pinReloj' => null, 'marcaDirecta' => false],
    ]);
    DB::table('asistencias')->insert([
        ['ci' => '1', 'fecha' => now()->toDateString(), 'hora' => now()->toDateTimeString(), 'tipo' => 'R'],
        ['ci' => '2', 'fecha' => now()->toDateString(), 'hora' => now()->toDateTimeString(), 'tipo' => 'M'],
    ]);

    $this->get(route('marcaciones.index', ['tipo' => 'R']))
        ->assertOk()
        ->assertSee('Relojero')
        ->assertDontSee('Manualino');
});

test('una marcación manual no se pinta con el color de reloj', function () {
    DB::table('personas')->insert([
        'ci' => '333', 'paterno' => 'Manual', 'materno' => null, 'nombres' => 'Uno', 'pinReloj' => null, 'marcaDirecta' => false,
    ]);
    DB::table('asistencias')->insert([
        'ci' => '333',
        'fecha' => now()->toDateString(),
        'hora' => now()->toDateTimeString(),
        'tipo' => 'M',
    ]);

    // El CSS estático del layout siempre define .pill--ok (regla, no dato);
    // se verifica el <span> renderizado puntual, no una búsqueda de substring.
    $this->get(route('marcaciones.index'))
        ->assertOk()
        ->assertSee('<span class="pill pill--advertencia">M</span>', escape: false);
});

test('importa un csv nuevo y crea la marcación en Asistencia', function () {
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '4176235', 'Paterno' => 'Perez', 'Materno' => null, 'Nombres' => 'Juan', 'PinReloj' => '4176235', 'MarcaDirecta' => false,
    ]);

    $csv = "\u{FEFF}CI/ID,Nombre,Fecha,Hora\n4176235,\"Perez Juan\",15/07/2026,08:05:00\n";
    $archivo = UploadedFile::fake()->createWithContent('marcaciones.csv', $csv);

    $this->post(route('marcaciones.importar'), ['archivo' => $archivo])
        ->assertRedirect()
        ->assertSessionHas('estado', fn (string $mensaje) => str_contains($mensaje, '1 marcación(es) nueva(s)'));

    expect(DB::connection('sia')->table('Asistencia')->where('IdPersona', '4176235')->count())->toBe(1);
});

test('no duplica una marcación que ya existe en Asistencia', function () {
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '555', 'Paterno' => 'Gomez', 'Materno' => null, 'Nombres' => 'Ana', 'PinReloj' => '555', 'MarcaDirecta' => false,
    ]);
    DB::connection('sia')->table('Asistencia')->insert([
        'IdPersona' => '555',
        'Fecha' => '2026-07-15 00:00:00',
        'Hora' => '1899-12-30 08:05:00',
        'Tipo' => 'R',
    ]);

    $csv = "CI/ID,Nombre,Fecha,Hora\n555,\"Gomez Ana\",15/07/2026,08:05:00\n";
    $archivo = UploadedFile::fake()->createWithContent('marcaciones.csv', $csv);

    $this->post(route('marcaciones.importar'), ['archivo' => $archivo])
        ->assertRedirect()
        ->assertSessionHas('estado', fn (string $mensaje) => str_contains($mensaje, '0 marcación(es) nueva(s)') && str_contains($mensaje, '1 ya existían'));

    expect(DB::connection('sia')->table('Asistencia')->where('IdPersona', '555')->count())->toBe(1);
});

test('una fila sin funcionario vinculado no se inserta y queda contada', function () {
    $csv = "CI/ID,Nombre,Fecha,Hora\n999999,\"Sin Registro\",15/07/2026,08:05:00\n";
    $archivo = UploadedFile::fake()->createWithContent('marcaciones.csv', $csv);

    $this->post(route('marcaciones.importar'), ['archivo' => $archivo])
        ->assertRedirect()
        ->assertSessionHas('estado', fn (string $mensaje) => str_contains($mensaje, '1 sin funcionario vinculado'));

    expect(DB::connection('sia')->table('Asistencia')->count())->toBe(0);
});

test('importa un csv reguardado desde Excel con separador punto y coma', function () {
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '4176235', 'Paterno' => 'Perez', 'Materno' => null, 'Nombres' => 'Juan', 'PinReloj' => '4176235', 'MarcaDirecta' => false,
    ]);

    $csv = "CI/ID;Nombre;Fecha;Hora\n4176235;\"Perez Juan\";15/07/2026;08:05:00\n";
    $archivo = UploadedFile::fake()->createWithContent('marcaciones.csv', $csv);

    $this->post(route('marcaciones.importar'), ['archivo' => $archivo])
        ->assertRedirect()
        ->assertSessionHas('estado', fn (string $mensaje) => str_contains($mensaje, '1 marcación(es) nueva(s)'));

    expect(DB::connection('sia')->table('Asistencia')->where('IdPersona', '4176235')->count())->toBe(1);
});

test('importa filas con la hora sin segundos', function () {
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '999', 'Paterno' => 'Sinseg', 'Materno' => null, 'Nombres' => 'Test', 'PinReloj' => '999', 'MarcaDirecta' => false,
    ]);

    $csv = "CI/ID,Nombre,Fecha,Hora\n999,\"Sinseg Test\",15/07/2026,08:05\n";
    $archivo = UploadedFile::fake()->createWithContent('marcaciones.csv', $csv);

    $this->post(route('marcaciones.importar'), ['archivo' => $archivo])
        ->assertRedirect()
        ->assertSessionHas('estado', fn (string $mensaje) => str_contains($mensaje, '1 marcación(es) nueva(s)'));

    expect(DB::connection('sia')->table('Asistencia')->where('IdPersona', '999')->count())->toBe(1);
});

test('una fila con fecha basura futura del reloj (RTC) se descarta y no rompe el import', function () {
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '7655482', 'Paterno' => 'Torrez', 'Materno' => null, 'Nombres' => 'Rene', 'PinReloj' => '7655482', 'MarcaDirecta' => false,
    ]);

    $csv = "CI/ID,Nombre,Fecha,Hora\n7655482,\"Torrez Rene\",19/08/2103,02:52:58\n";
    $archivo = UploadedFile::fake()->createWithContent('marcaciones.csv', $csv);

    $this->post(route('marcaciones.importar'), ['archivo' => $archivo])
        ->assertRedirect()
        ->assertSessionHas('estado', fn (string $mensaje) => str_contains($mensaje, '0 marcación(es) nueva(s)') && str_contains($mensaje, '1 fila(s) inválida(s)'));

    expect(DB::connection('sia')->table('Asistencia')->count())->toBe(0);
});

test('un usuario sin permiso de crear marcaciones no puede importar', function () {
    $this->actingAs(User::factory()->create());

    $archivo = UploadedFile::fake()->createWithContent('marcaciones.csv', "CI/ID,Nombre,Fecha,Hora\n");

    $this->post(route('marcaciones.importar'), ['archivo' => $archivo])->assertForbidden();
});

test('un invitado no puede importar marcaciones', function () {
    auth()->logout();

    $archivo = UploadedFile::fake()->createWithContent('marcaciones.csv', "CI/ID,Nombre,Fecha,Hora\n");

    $this->post(route('marcaciones.importar'), ['archivo' => $archivo])->assertRedirect();
});
