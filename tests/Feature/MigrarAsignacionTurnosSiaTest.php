<?php

use App\Models\Sia\DiaTurno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    fakeSiaDatabase();
});

/**
 * Inserta una asignación de turno en el SIA falso, sobrescribible.
 *
 * @param  array<string, mixed>  $extra
 */
function insertarAsignacionSia(array $extra = []): void
{
    DB::connection('sia')->table('AsignacionTurnos')->insert([
        'IdPersona' => '13223966',
        'IdTurno' => '8DW',
        'Desde' => '2026-07-21 00:00:00',
        'Hasta' => '2026-11-30 00:00:00',
        ...$extra,
    ]);
}

test('copia las asignaciones del SIA a la base local con id y timestamps', function () {
    insertarAsignacionSia();
    insertarAsignacionSia(['IdTurno' => 'EKX']);

    $this->artisan('sia:migrar-asignacion-turnos')->assertSuccessful();

    expect(DB::table('asignacion_turnos')->count())->toBe(2);

    $fila = DB::table('asignacion_turnos')->first();
    expect($fila->id)->toBeGreaterThan(0)
        ->and($fila->created_at)->not->toBeNull();
});

test('mapea IdPersona→ci, mantiene idTurno y resuelve turno_id contra turnos local', function () {
    // Turno local con el que se cruza: se migra un DiaTurno del SIA primero.
    DiaTurno::factory()->create(['IdTurno' => '8DW']);
    $this->artisan('sia:migrar-horarios')->assertSuccessful();
    $turnoId = DB::table('turnos')->where('idTurno', '8DW')->value('id');

    insertarAsignacionSia(['IdPersona' => '13223966    ', 'IdTurno' => '8DW']);

    $this->artisan('sia:migrar-asignacion-turnos')->assertSuccessful();

    $local = DB::table('asignacion_turnos')->where('ci', '13223966')->first();

    expect($local)->not->toBeNull()
        ->and($local->idTurno)->toBe('8DW')
        ->and($local->turno_id)->toBe($turnoId);
});

test('turno_id queda null si el idTurno no cruza con ningún turno', function () {
    insertarAsignacionSia(['IdTurno' => 'ZZZ']);

    $this->artisan('sia:migrar-asignacion-turnos')->assertSuccessful();

    expect(DB::table('asignacion_turnos')->value('turno_id'))->toBeNull();
});

test('es idempotente: correrlo dos veces no duplica', function () {
    insertarAsignacionSia();
    insertarAsignacionSia(['IdTurno' => 'EKX']);

    $this->artisan('sia:migrar-asignacion-turnos')->assertSuccessful();
    $this->artisan('sia:migrar-asignacion-turnos')->assertSuccessful();

    expect(DB::table('asignacion_turnos')->count())->toBe(2);
});

test('no escribe sobre la base del SIA (origen intacto)', function () {
    insertarAsignacionSia();

    $this->artisan('sia:migrar-asignacion-turnos')->assertSuccessful();

    expect(DB::connection('sia')->table('AsignacionTurnos')->count())->toBe(1);
});
