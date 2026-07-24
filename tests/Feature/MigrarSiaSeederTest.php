<?php

use App\Models\Sia\DiaTurno;
use App\Models\Sia\Persona;
use Database\Seeders\MigrarSiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    fakeSiaDatabase();
});

test('el seeder corre toda la migración del SIA en orden y resuelve turno_id', function () {
    // Datos de origen en el SIA falso.
    Persona::factory()->create(['IdPersona' => '111']);
    DiaTurno::factory()->create(['IdTurno' => '8DW']);
    DB::connection('sia')->table('AsignacionTurnos')->insert([
        'IdPersona' => '111',
        'IdTurno' => '8DW',
        'Desde' => '2026-07-21 00:00:00',
        'Hasta' => '2026-11-30 00:00:00',
    ]);

    $this->seed(MigrarSiaSeeder::class);

    // Todas las tablas locales quedaron pobladas.
    expect(DB::table('personas')->count())->toBe(1)
        ->and(DB::table('turnos')->count())->toBe(1)
        ->and(DB::table('asignacion_turnos')->count())->toBe(1);

    // La FK turno_id se resolvió porque el seeder migra horarios antes.
    $turnoId = DB::table('turnos')->where('idTurno', '8DW')->value('id');
    expect(DB::table('asignacion_turnos')->value('turno_id'))->toBe($turnoId);
});

test('el seeder es idempotente: correrlo dos veces no duplica', function () {
    Persona::factory()->count(2)->create();

    $this->seed(MigrarSiaSeeder::class);
    $this->seed(MigrarSiaSeeder::class);

    expect(DB::table('personas')->count())->toBe(2);
});
