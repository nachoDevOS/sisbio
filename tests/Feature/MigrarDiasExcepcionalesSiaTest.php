<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    fakeSiaDatabase();
});

test('copia los días excepcionales del SIA a la base local con id y timestamps', function () {
    DB::connection('sia')->table('Calendario')->insert([
        ['Fecha' => '2013-05-30 00:00:00', 'MotivoInasistencia' => 'Corpus Christi'],
        ['Fecha' => '2013-05-27 00:00:00', 'MotivoInasistencia' => 'Feriad Santisima Trinidad'],
        ['Fecha' => '2013-05-29 00:00:00', 'MotivoInasistencia' => null],
    ]);

    $this->artisan('sia:migrar-dias-excepcionales')->assertSuccessful();

    expect(DB::table('dias_excepcionales')->count())->toBe(3);

    $fila = DB::table('dias_excepcionales')->first();
    expect($fila->id)->toBeGreaterThan(0)
        ->and($fila->created_at)->not->toBeNull();
});

test('mapea a camelCase y preserva el motivo', function () {
    DB::connection('sia')->table('Calendario')->insert([
        'Fecha' => '2013-05-30 00:00:00',
        'MotivoInasistencia' => 'Corpus Christi',
    ]);

    $this->artisan('sia:migrar-dias-excepcionales')->assertSuccessful();

    $local = DB::table('dias_excepcionales')->first();

    expect($local->motivoInasistencia)->toBe('Corpus Christi')
        ->and($local->fecha)->toContain('2013-05-30');
});

test('es idempotente: correrlo dos veces no duplica', function () {
    DB::connection('sia')->table('Calendario')->insert([
        ['Fecha' => '2013-05-30 00:00:00', 'MotivoInasistencia' => 'Corpus Christi'],
        ['Fecha' => '2013-05-27 00:00:00', 'MotivoInasistencia' => null],
    ]);

    $this->artisan('sia:migrar-dias-excepcionales')->assertSuccessful();
    $this->artisan('sia:migrar-dias-excepcionales')->assertSuccessful();

    expect(DB::table('dias_excepcionales')->count())->toBe(2);
});

test('no escribe sobre la base del SIA (origen intacto)', function () {
    DB::connection('sia')->table('Calendario')->insert([
        'Fecha' => '2013-05-30 00:00:00',
        'MotivoInasistencia' => 'Corpus Christi',
    ]);

    $this->artisan('sia:migrar-dias-excepcionales')->assertSuccessful();

    expect(DB::connection('sia')->table('Calendario')->count())->toBe(1);
});
