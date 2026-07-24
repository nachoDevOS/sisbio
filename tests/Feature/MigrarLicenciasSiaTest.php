<?php

use App\Models\Sia\DiaTurno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // SIA falso (sqlite en memoria) con Licencias; la base local trae la tabla
    // `licencias` de la migración propia.
    fakeSiaDatabase();
});

/**
 * Inserta una licencia en el SIA falso con valores por defecto sobrescribibles.
 *
 * @param  array<string, mixed>  $extra
 */
function insertarLicenciaSia(array $extra = []): void
{
    DB::connection('sia')->table('Licencias')->insert([
        'FechaPedido' => '2026-07-08 09:45:56',
        'Usuario' => 'mvillavicencio',
        'Fecha' => '2026-06-22 00:00:00',
        'IdPersona' => '10790063',
        'IdTurno' => '8DW',
        'LEntra' => null,
        'LSale' => null,
        'TCompleto' => true,
        'Motivo' => 'COMISION',
        'GoceHaberes' => true,
        ...$extra,
    ]);
}

test('copia las licencias del SIA a la base local con id y timestamps', function () {
    insertarLicenciaSia();
    insertarLicenciaSia(['IdPersona' => '4191164', 'IdTurno' => 'EKX']);

    $this->artisan('sia:migrar-licencias')->assertSuccessful();

    expect(DB::table('licencias')->count())->toBe(2);

    $fila = DB::table('licencias')->first();
    expect($fila->id)->toBeGreaterThan(0)
        ->and($fila->created_at)->not->toBeNull();
});

test('mapea IdPersona→ci y preserva los campos, recortando el relleno', function () {
    insertarLicenciaSia([
        'IdPersona' => '10790063    ', // char(12) con relleno; en local va a ci.
        'IdTurno' => '8DW',
        'Motivo' => 'INGRESO AL BIOMETRICO',
        'LEntra' => '1899-12-30 08:00:00',
    ]);

    $this->artisan('sia:migrar-licencias')->assertSuccessful();

    $local = DB::table('licencias')->where('ci', '10790063')->first();

    expect($local)->not->toBeNull()
        ->and($local->usuario)->toBe('mvillavicencio')
        ->and($local->idTurno)->toBe('8DW')
        ->and($local->motivo)->toBe('INGRESO AL BIOMETRICO')
        ->and($local->lEntra)->toContain('08:00:00');
});

test('resuelve turno_id cruzando idTurno con turnos local', function () {
    // Turno local con el que se cruza: se migra un DiaTurno del SIA primero.
    DiaTurno::factory()->create(['IdTurno' => '8DW']);
    $this->artisan('sia:migrar-horarios')->assertSuccessful();
    $turnoId = DB::table('turnos')->where('idTurno', '8DW')->value('id');

    insertarLicenciaSia(['IdTurno' => '8DW']);

    $this->artisan('sia:migrar-licencias')->assertSuccessful();

    expect(DB::table('licencias')->value('turno_id'))->toBe($turnoId);
});

test('turno_id queda null si el idTurno no cruza con ningún turno', function () {
    insertarLicenciaSia(['IdTurno' => 'ZZZ']);

    $this->artisan('sia:migrar-licencias')->assertSuccessful();

    expect(DB::table('licencias')->value('turno_id'))->toBeNull();
});

test('es idempotente: correrlo dos veces no duplica', function () {
    insertarLicenciaSia();
    insertarLicenciaSia(['IdPersona' => '4191164', 'IdTurno' => 'EKX']);

    $this->artisan('sia:migrar-licencias')->assertSuccessful();
    $this->artisan('sia:migrar-licencias')->assertSuccessful();

    expect(DB::table('licencias')->count())->toBe(2);
});

test('no escribe sobre la base del SIA (origen intacto)', function () {
    insertarLicenciaSia();

    $this->artisan('sia:migrar-licencias')->assertSuccessful();

    expect(DB::connection('sia')->table('Licencias')->count())->toBe(1);
});
