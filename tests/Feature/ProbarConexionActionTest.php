<?php

use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Models\Equipo;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.device_service.url', 'http://microservicio.test');
    config()->set('services.device_service.token', 'token-de-prueba');

    // El panel exige un usuario autenticado con permisos (Shield).
    $this->actingAs(asSuperAdmin());
});

test('probar conexión marca el equipo en línea y guarda el algoritmo', function () {
    Http::fake([
        'microservicio.test/device/info*' => Http::response([
            'en_linea' => true,
            'algoritmo' => 'ZLM60_TFT | Ver 6.60',
        ], 200),
    ]);

    $equipo = Equipo::factory()->create(['en_linea' => false, 'algoritmo' => null]);

    Livewire::test(ListEquipos::class)
        ->callAction(TestAction::make('probar_conexion')->table($equipo))
        ->assertNotified('Equipo en línea');

    expect($equipo->refresh())
        ->en_linea->toBeTrue()
        ->algoritmo->toBe('ZLM60_TFT | Ver 6.60');

    expect($equipo->ultima_sync)->not->toBeNull();
});

test('la vista de marcaciones muestra el nombre y el id del empleado', function () {
    $html = view('filament.equipos.marcaciones', [
        'marcaciones' => [
            ['uid' => 188, 'user_id' => '7633685', 'nombre' => 'Ignacio Molina Guzman', 'timestamp' => '2026-07-09T21:05:48', 'estado' => 1, 'verificacion' => 0],
        ],
        'error' => null,
    ])->render();

    expect($html)
        ->toContain('Ignacio Molina Guzman')
        ->toContain('7633685')
        ->toContain('09/07/2026');
});

test('la vista de marcaciones muestra el error cuando el equipo no responde', function () {
    $html = view('filament.equipos.marcaciones', [
        'marcaciones' => [],
        'error' => 'No se pudo conectar con el equipo',
    ])->render();

    expect($html)->toContain('No se pudo conectar con el equipo');
});

test('probar conexión marca fuera de línea y notifica el error si el equipo no responde', function () {
    Http::fake([
        'microservicio.test/device/info*' => Http::response([
            'detail' => 'No se pudo conectar con el equipo 192.168.1.201:4370',
        ], 503),
    ]);

    $equipo = Equipo::factory()->create(['en_linea' => true]);

    Livewire::test(ListEquipos::class)
        ->callAction(TestAction::make('probar_conexion')->table($equipo))
        ->assertNotified('No se pudo conectar');

    expect($equipo->refresh()->en_linea)->toBeFalse();
});
