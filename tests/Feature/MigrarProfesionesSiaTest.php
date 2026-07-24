<?php

use App\Models\Sia\Profesion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    fakeSiaDatabase();
});

test('copia las profesiones del SIA a la base local con id y timestamps', function () {
    Profesion::factory()->count(3)->create();

    $this->artisan('sia:migrar-profesiones')->assertSuccessful();

    expect(DB::table('profesiones')->count())->toBe(3);

    $fila = DB::table('profesiones')->first();
    expect($fila->id)->toBeGreaterThan(0)
        ->and($fila->created_at)->not->toBeNull();
});

test('mapea a camelCase y preserva los campos, recortando el relleno', function () {
    DB::connection('sia')->table('Profesiones')->insert([
        'CodigoProfesion' => '01',
        'NombreProfesion' => 'INGENIERO',
    ]);

    $this->artisan('sia:migrar-profesiones')->assertSuccessful();

    $local = DB::table('profesiones')->where('codigoProfesion', '01')->first();

    expect($local)->not->toBeNull()
        ->and($local->nombreProfesion)->toBe('INGENIERO');
});

test('es idempotente: correrlo dos veces no duplica', function () {
    Profesion::factory()->count(2)->create();

    $this->artisan('sia:migrar-profesiones')->assertSuccessful();
    $this->artisan('sia:migrar-profesiones')->assertSuccessful();

    expect(DB::table('profesiones')->count())->toBe(2);
});

test('no escribe sobre la base del SIA (origen intacto)', function () {
    Profesion::factory()->count(2)->create();

    $this->artisan('sia:migrar-profesiones')->assertSuccessful();

    expect(DB::connection('sia')->table('Profesiones')->count())->toBe(2);
});
