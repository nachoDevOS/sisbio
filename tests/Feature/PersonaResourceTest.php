<?php

use App\Filament\Resources\Personas\Pages\CreatePersona;
use App\Filament\Resources\Personas\Pages\EditPersona;
use App\Filament\Resources\Personas\Pages\ListPersonas;
use App\Filament\Resources\Personas\Pages\VerPersona;
use App\Filament\Resources\Personas\RelationManagers\MarcacionesRelationManager;
use App\Models\Sia\Asistencia;
use App\Models\Sia\Persona;
use App\Models\Sia\Profesion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    fakeSiaDatabase();
    $this->actingAs(asSuperAdmin());
});

test('lista los funcionarios del SIA', function () {
    $personas = Persona::factory()->count(3)->create();

    Livewire::test(ListPersonas::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($personas);
});

test('busca funcionarios por apellido', function () {
    Persona::factory()->create(['Paterno' => 'Zabaleta']);
    Persona::factory()->create(['Paterno' => 'Quiroga']);

    Livewire::test(ListPersonas::class)
        ->searchTable('Zabaleta')
        ->assertSee('Zabaleta')
        ->assertDontSee('Quiroga');
});

test('muestra la página de alta de funcionarios', function () {
    Livewire::test(CreatePersona::class)
        ->assertSuccessful();
});

test('da de alta un funcionario en la tabla Personas del SIA', function () {
    $profesion = Profesion::factory()->create(['NombreProfesion' => 'CONTADOR GENERAL']);

    Livewire::test(CreatePersona::class)
        ->fillForm([
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
        ->call('create')
        ->assertHasNoFormErrors();

    $persona = Persona::query()->find('1234567');

    expect($persona)->not->toBeNull()
        ->and($persona->Paterno)->toBe('Suárez')
        ->and($persona->Nombres)->toBe('Ana María')
        ->and($persona->OrigenId)->toBe('BE')
        ->and($persona->CodigoProfesion)->toBe($profesion->CodigoProfesion)
        // Sección "Control de asistencia" deshabilitada: se guardan los
        // valores por defecto, sin PIN y sin marcación con contraseña.
        ->and($persona->PinReloj)->toBeNull()
        ->and($persona->MarcaDirecta)->toBeFalse();
});

test('valida los campos obligatorios del alta', function () {
    Livewire::test(CreatePersona::class)
        ->fillForm([
            'IdPersona' => '',
            'Paterno' => '',
            'Nombres' => '',
            'FechaNacimiento' => null,
            'Sexo' => null,
            'EstadoCivil' => null,
            'CodigoProfesion' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'IdPersona' => 'required',
            'Paterno' => 'required',
            'Nombres' => 'required',
            'FechaNacimiento' => 'required',
            'Sexo' => 'required',
            'EstadoCivil' => 'required',
            'CodigoProfesion' => 'required',
        ]);

    expect(Persona::query()->count())->toBe(0);
});

test('rechaza un carnet ya registrado', function () {
    Persona::factory()->create(['IdPersona' => '9999999']);

    Livewire::test(CreatePersona::class)
        ->fillForm([
            'IdPersona' => '9999999',
            'Paterno' => 'Suárez',
            'Nombres' => 'Ana',
            'FechaNacimiento' => '1990-05-10',
            'Sexo' => 'F',
            'EstadoCivil' => 'S',
            'CodigoProfesion' => '00',
        ])
        ->call('create')
        ->assertHasFormErrors(['IdPersona' => 'unique']);

    expect(Persona::query()->count())->toBe(1);
});

test('muestra la ficha de detalle dentro del panel', function () {
    Profesion::factory()->create();
    $persona = Persona::factory()->create([
        'IdPersona' => '7778888',
        'Paterno' => 'Detalle',
        'Nombres' => 'Vista Completa',
    ]);

    Livewire::test(VerPersona::class, ['record' => '7778888'])
        ->assertSuccessful()
        ->assertSee('Detalle')
        ->assertSee('Vista Completa');
});

test('edita un funcionario desde el panel sin tocar el carnet', function () {
    $profesion = Profesion::factory()->create();
    $persona = Persona::factory()->create([
        'IdPersona' => '5551234',
        'Paterno' => 'Original',
        'CodigoProfesion' => $profesion->CodigoProfesion,
    ]);

    Livewire::test(EditPersona::class, ['record' => '5551234'])
        ->assertSuccessful()
        ->fillForm([
            'Paterno' => 'Cambiado',
            'Nombres' => 'Nuevo Nombre',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $persona->refresh();

    expect($persona->Paterno)->toBe('Cambiado')
        ->and($persona->Nombres)->toBe('Nuevo Nombre')
        ->and(trim($persona->IdPersona))->toBe('5551234');
});

test('la ficha de detalle incluye las marcaciones del funcionario', function () {
    $persona = Persona::factory()->create(['IdPersona' => '3334444']);

    Livewire::test(VerPersona::class, ['record' => '3334444'])
        ->assertSuccessful()
        ->assertSeeLivewire(MarcacionesRelationManager::class);
});

test('la tabla de marcaciones de la ficha solo muestra las del funcionario', function () {
    $persona = Persona::factory()->create(['IdPersona' => '3334444', 'Paterno' => 'Dueño']);
    $otro = Persona::factory()->create(['IdPersona' => '9998888', 'Paterno' => 'Ajeno']);

    Asistencia::factory()->create(['IdPersona' => $persona->IdPersona, 'Fecha' => today()]);
    Asistencia::factory()->create(['IdPersona' => $otro->IdPersona, 'Fecha' => today()]);

    Livewire::test(MarcacionesRelationManager::class, [
        'ownerRecord' => $persona,
        'pageClass' => VerPersona::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($persona->marcaciones)
        ->assertCanNotSeeTableRecords($otro->marcaciones);
});

test('filtra las marcaciones de la ficha por rango de fechas', function () {
    $persona = Persona::factory()->create(['IdPersona' => '3334444']);

    $vigente = Asistencia::factory()->create(['IdPersona' => $persona->IdPersona, 'Fecha' => today()]);
    $anterior = Asistencia::factory()->create(['IdPersona' => $persona->IdPersona, 'Fecha' => today()->subMonths(3)]);

    Livewire::test(MarcacionesRelationManager::class, [
        'ownerRecord' => $persona,
        'pageClass' => VerPersona::class,
    ])
        ->filterTable('rango', ['desde' => null, 'hasta' => null])
        ->assertCanSeeTableRecords([$vigente, $anterior]);
});
