<?php

use App\Filament\Resources\Marcaciones\Pages\ListMarcaciones;
use App\Models\Sia\Asistencia;
use App\Models\Sia\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    fakeSiaDatabase();
    $this->actingAs(asSuperAdmin());
});

test('lista las marcaciones del mes con los datos del funcionario', function () {
    $persona = Persona::factory()->create(['Paterno' => 'Justiniano', 'Nombres' => 'Carla']);

    Asistencia::factory()->count(2)->create([
        'IdPersona' => $persona->IdPersona,
        'Fecha' => today(),
    ]);

    Livewire::test(ListMarcaciones::class)
        ->assertSuccessful()
        ->assertSee('Justiniano')
        ->assertSee(trim($persona->IdPersona));
});

test('el filtro por defecto oculta marcaciones de meses anteriores', function () {
    $anterior = Persona::factory()->create(['Paterno' => 'Antiguano']);
    $vigente = Persona::factory()->create(['Paterno' => 'Vigente']);

    Asistencia::factory()->create(['IdPersona' => $anterior->IdPersona, 'Fecha' => today()->subMonths(3)]);
    Asistencia::factory()->create(['IdPersona' => $vigente->IdPersona, 'Fecha' => today()]);

    Livewire::test(ListMarcaciones::class)
        ->assertSuccessful()
        ->assertSee('Vigente')
        ->assertDontSee('Antiguano');
});

test('el filtro por defecto oculta marcaciones con fechas basura futuras', function () {
    $futuro = Persona::factory()->create(['Paterno' => 'Futurista']);
    $vigente = Persona::factory()->create(['Paterno' => 'Vigente']);

    // El SIA real arrastra registros con años 2064/2103.
    Asistencia::factory()->create(['IdPersona' => $futuro->IdPersona, 'Fecha' => today()->addYears(70)]);
    Asistencia::factory()->create(['IdPersona' => $vigente->IdPersona, 'Fecha' => today()]);

    Livewire::test(ListMarcaciones::class)
        ->assertSuccessful()
        ->assertSee('Vigente')
        ->assertDontSee('Futurista');
});

test('filtra por tipo de marcación', function () {
    $conReloj = Persona::factory()->create(['Paterno' => 'Relojero']);
    $conManual = Persona::factory()->create(['Paterno' => 'Manualino']);

    Asistencia::factory()->create(['IdPersona' => $conReloj->IdPersona, 'Tipo' => Asistencia::TIPO_RELOJ]);
    Asistencia::factory()->create(['IdPersona' => $conManual->IdPersona, 'Tipo' => Asistencia::TIPO_MANUAL]);

    Livewire::test(ListMarcaciones::class)
        ->filterTable('Tipo', Asistencia::TIPO_RELOJ)
        ->assertSee('Relojero')
        ->assertDontSee('Manualino');
});

test('siempre ordena por más reciente primero, sin control del usuario', function () {
    $temprano = Persona::factory()->create(['Paterno' => 'Madrugador']);
    $tarde = Persona::factory()->create(['Paterno' => 'Vespertino']);

    Asistencia::factory()->create(['IdPersona' => $temprano->IdPersona, 'Fecha' => today(), 'Hora' => '1899-12-30 08:00:00']);
    Asistencia::factory()->create(['IdPersona' => $tarde->IdPersona, 'Fecha' => today(), 'Hora' => '1899-12-30 17:00:00']);

    Livewire::test(ListMarcaciones::class)
        ->assertSuccessful()
        ->assertSeeInOrder(['Vespertino', 'Madrugador']);
});

test('busca marcaciones por apellido del funcionario', function () {
    $buscado = Persona::factory()->create(['Paterno' => 'Zabaleta']);
    $otro = Persona::factory()->create(['Paterno' => 'Quiroga']);

    Asistencia::factory()->create(['IdPersona' => $buscado->IdPersona]);
    Asistencia::factory()->create(['IdPersona' => $otro->IdPersona]);

    Livewire::test(ListMarcaciones::class)
        ->filterTable('rango', ['buscar' => 'Zabaleta'])
        ->assertSee('Zabaleta')
        ->assertDontSee('Quiroga');
});

test('busca marcaciones por CI del funcionario', function () {
    $buscado = Persona::factory()->create(['Paterno' => 'Rocabado']);
    $otro = Persona::factory()->create(['Paterno' => 'Salvatierra']);

    Asistencia::factory()->create(['IdPersona' => $buscado->IdPersona]);
    Asistencia::factory()->create(['IdPersona' => $otro->IdPersona]);

    Livewire::test(ListMarcaciones::class)
        ->filterTable('rango', ['buscar' => trim($buscado->IdPersona)])
        ->assertSee('Rocabado')
        ->assertDontSee('Salvatierra');
});

test('vaciar los campos de fecha muestra marcaciones de todo el historial', function () {
    $anterior = Persona::factory()->create(['Paterno' => 'Antiguano']);
    $vigente = Persona::factory()->create(['Paterno' => 'Vigente']);

    Asistencia::factory()->create(['IdPersona' => $anterior->IdPersona, 'Fecha' => today()->subMonths(3)]);
    Asistencia::factory()->create(['IdPersona' => $vigente->IdPersona, 'Fecha' => today()]);

    Livewire::test(ListMarcaciones::class)
        ->filterTable('rango', ['desde' => null, 'hasta' => null])
        ->assertSuccessful()
        ->assertSee('Vigente')
        ->assertSee('Antiguano');
});
