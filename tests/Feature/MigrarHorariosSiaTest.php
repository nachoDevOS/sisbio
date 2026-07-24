<?php

use App\Models\Sia\DiaTurno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    fakeSiaDatabase();
});

test('copia los horarios del SIA a la base local con id y timestamps', function () {
    DiaTurno::factory()->count(3)->create();

    $this->artisan('sia:migrar-horarios')->assertSuccessful();

    expect(DB::table('turnos')->count())->toBe(3);

    $fila = DB::table('turnos')->first();
    expect($fila->id)->toBeGreaterThan(0)
        ->and($fila->created_at)->not->toBeNull();
});

test('mapea a camelCase y preserva los campos, recortando el relleno de los char()', function () {
    DB::connection('sia')->table('DiaTurnos')->insert([
        'IdTurno' => 'M1 ', // char(3) con relleno.
        'Dia' => '2',
        'NombreTurno' => 'Mañana',
        'HEntrada' => '1899-12-30 08:00:00',
        'HSalida' => '1899-12-30 16:00:00',
        'HTolerancia' => '1899-12-30 08:10:00',
        'EMinima' => '1899-12-30 07:00:00',
        'EMaxima' => '1899-12-30 10:00:00',
        'SMinima' => '1899-12-30 16:00:00',
        'SMaxima' => '1899-12-30 23:59:00',
        'STolerancia' => '1899-12-30 16:00:00',
        'HTrabajadas' => 8,
        'SiguienteDia' => false,
    ]);

    $this->artisan('sia:migrar-horarios')->assertSuccessful();

    $local = DB::table('turnos')->where('idTurno', 'M1')->first();

    expect($local)->not->toBeNull()
        ->and($local->nombreTurno)->toBe('Mañana')
        ->and($local->dia)->toBe('2')
        ->and($local->hEntrada)->toContain('08:00:00');
});

test('es idempotente: correrlo dos veces no duplica', function () {
    DiaTurno::factory()->count(2)->create();

    $this->artisan('sia:migrar-horarios')->assertSuccessful();
    $this->artisan('sia:migrar-horarios')->assertSuccessful();

    expect(DB::table('turnos')->count())->toBe(2);
});

test('no escribe sobre la base del SIA (origen intacto)', function () {
    DiaTurno::factory()->count(2)->create();

    $this->artisan('sia:migrar-horarios')->assertSuccessful();

    expect(DB::connection('sia')->table('DiaTurnos')->count())->toBe(2);
});
