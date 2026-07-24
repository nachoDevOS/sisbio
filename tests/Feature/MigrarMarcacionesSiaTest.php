<?php

use App\Models\Sia\Asistencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // SIA falso (sqlite en memoria) con Asistencia; la base local trae la tabla
    // `asistencias` de la migración propia.
    fakeSiaDatabase();
});

test('copia las marcaciones del SIA a la base local con id y timestamps', function () {
    Asistencia::factory()->count(3)->create();

    $this->artisan('sia:migrar-marcaciones')->assertSuccessful();

    expect(DB::table('asistencias')->count())->toBe(3);

    $fila = DB::table('asistencias')->first();
    expect($fila->id)->toBeGreaterThan(0)
        ->and($fila->created_at)->not->toBeNull()
        ->and($fila->updated_at)->not->toBeNull();
});

test('mapea IdPersona→ci y preserva los campos, recortando el relleno', function () {
    DB::connection('sia')->table('Asistencia')->insert([
        'IdPersona' => '12345678    ', // char(12) con relleno; en local va a ci.
        'Fecha' => '2026-07-20 00:00:00',
        'Hora' => '1899-12-30 08:15:30',
        'Tipo' => Asistencia::TIPO_RELOJ,
    ]);

    $this->artisan('sia:migrar-marcaciones')->assertSuccessful();

    $local = DB::table('asistencias')->where('ci', '12345678')->first();

    expect($local)->not->toBeNull()
        ->and($local->tipo)->toBe('R')
        ->and($local->hora)->toContain('08:15:30');
});

test('es idempotente: correrlo dos veces no duplica', function () {
    Asistencia::factory()->count(4)->create();

    $this->artisan('sia:migrar-marcaciones')->assertSuccessful();
    $this->artisan('sia:migrar-marcaciones')->assertSuccessful();

    expect(DB::table('asistencias')->count())->toBe(4);
});

test('respeta el tamaño de lote y copia todo', function () {
    Asistencia::factory()->count(5)->create();

    $this->artisan('sia:migrar-marcaciones', ['--chunk' => 2])->assertSuccessful();

    expect(DB::table('asistencias')->count())->toBe(5);
});

test('no escribe sobre la base del SIA (origen intacto)', function () {
    Asistencia::factory()->count(3)->create();

    $this->artisan('sia:migrar-marcaciones')->assertSuccessful();

    expect(DB::connection('sia')->table('Asistencia')->count())->toBe(3);
});
