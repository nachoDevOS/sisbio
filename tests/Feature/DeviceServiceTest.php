<?php

use App\Exceptions\DeviceServiceException;
use App\Models\Equipo;
use App\Services\DeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Config controlada para el microservicio en las pruebas.
    config()->set('services.device_service.url', 'http://microservicio.test');
    config()->set('services.device_service.token', 'token-de-prueba');
});

test('info devuelve los datos del equipo cuando el microservicio responde ok', function () {
    Http::fake([
        'microservicio.test/device/info*' => Http::response([
            'en_linea' => true,
            'nombre' => 'iClock',
            'algoritmo' => 'ZLM60_TFT | Ver 6.60',
        ], 200),
    ]);

    $equipo = Equipo::factory()->create(['ip' => '192.168.1.201', 'puerto' => 4370, 'comm_key' => 0]);

    $info = app(DeviceService::class)->info($equipo);

    expect($info['algoritmo'])->toBe('ZLM60_TFT | Ver 6.60')
        ->and($info['en_linea'])->toBeTrue();

    // Verifica que se envió el token y los parámetros correctos del equipo.
    Http::assertSent(function (Request $request) {
        return $request->hasHeader('X-Auth-Token', 'token-de-prueba')
            && str_contains($request->url(), 'ip=192.168.1.201')
            && str_contains($request->url(), 'port=4370');
    });
});

test('info lanza excepción con el detalle cuando el equipo no responde', function () {
    Http::fake([
        'microservicio.test/device/info*' => Http::response([
            'detail' => 'No se pudo conectar con el equipo 192.168.1.201:4370: can\'t reach device',
        ], 503),
    ]);

    $equipo = Equipo::factory()->create();

    app(DeviceService::class)->info($equipo);
})->throws(DeviceServiceException::class, 'No se pudo conectar con el equipo');

test('attendance devuelve las marcaciones del equipo', function () {
    Http::fake([
        'microservicio.test/device/attendance*' => Http::response([
            'en_linea' => true,
            'total' => 2,
            'marcaciones' => [
                ['uid' => 188, 'user_id' => '7633685', 'timestamp' => '2026-07-09T21:05:48', 'estado' => 1, 'verificacion' => 0],
                ['uid' => 187, 'user_id' => '1', 'timestamp' => '2026-07-09T20:34:41', 'estado' => 1, 'verificacion' => 0],
            ],
        ], 200),
    ]);

    $equipo = Equipo::factory()->create();

    $respuesta = app(DeviceService::class)->attendance($equipo);

    expect($respuesta['total'])->toBe(2)
        ->and($respuesta['marcaciones'])->toHaveCount(2)
        ->and($respuesta['marcaciones'][0]['user_id'])->toBe('7633685');
});

test('info lanza excepción clara cuando el microservicio está caído', function () {
    // Simula que ni siquiera se puede establecer conexión con el microservicio.
    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    $equipo = Equipo::factory()->create();

    app(DeviceService::class)->info($equipo);
})->throws(DeviceServiceException::class, 'No se pudo contactar al microservicio');
