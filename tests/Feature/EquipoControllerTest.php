<?php

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());

    config()->set('services.device_service.url', 'http://microservicio.test');
    config()->set('services.device_service.token', 'token-de-prueba');
});

test('el listado muestra los equipos registrados', function () {
    $equipo = Equipo::factory()->create(['nombre' => 'iClock Entrada']);

    $this->get(route('equipos.index'))
        ->assertOk()
        ->assertSee('iClock Entrada');
});

test('el listado tiene el modal para descargar el CSV por rango de fechas de cada equipo', function () {
    $equipo = Equipo::factory()->create();

    $this->get(route('equipos.index'))
        ->assertOk()
        ->assertSee(route('equipos.marcaciones.exportar', $equipo), escape: false)
        ->assertSee('name="desde"', escape: false)
        ->assertSee('name="hasta"', escape: false);
});

test('muestra el formulario de alta', function () {
    $this->get(route('equipos.create'))
        ->assertOk()
        ->assertSee('Nuevo equipo');
});

test('guarda un equipo nuevo y redirige al listado', function () {
    $datos = [
        'nombre' => 'iClock Bodega',
        'ip' => '192.168.1.60',
        'puerto' => 4370,
        'comm_key' => 0,
        'ubicacion' => 'Bodega',
        'es_master' => '1',
        'activo' => '1',
    ];

    $this->post(route('equipos.store'), $datos)
        ->assertRedirect(route('equipos.index'))
        ->assertSessionHas('estado');

    $this->assertDatabaseHas('equipos', [
        'nombre' => 'iClock Bodega',
        'ip' => '192.168.1.60',
        'es_master' => true,
        'activo' => true,
    ]);
});

test('rechaza una IP inválida', function () {
    $this->post(route('equipos.store'), [
        'nombre' => 'Malo',
        'ip' => 'no-es-ip',
        'puerto' => 4370,
        'comm_key' => 0,
    ])->assertSessionHasErrors('ip');

    $this->assertDatabaseCount('equipos', 0);
});

test('rechaza IP + puerto duplicados', function () {
    Equipo::factory()->create(['ip' => '192.168.1.70', 'puerto' => 4370]);

    $this->post(route('equipos.store'), [
        'nombre' => 'Duplicado',
        'ip' => '192.168.1.70',
        'puerto' => 4370,
        'comm_key' => 0,
    ])->assertSessionHasErrors('ip');

    $this->assertDatabaseCount('equipos', 1);
});

test('muestra la ficha de un equipo', function () {
    $equipo = Equipo::factory()->create(['nombre' => 'iClock Ficha']);

    $this->get(route('equipos.show', $equipo))
        ->assertOk()
        ->assertSee('iClock Ficha');
});

test('muestra el formulario de edición con los datos actuales', function () {
    $equipo = Equipo::factory()->create(['nombre' => 'iClock Editar']);

    $this->get(route('equipos.edit', $equipo))
        ->assertOk()
        ->assertSee('iClock Editar');
});

test('actualiza un equipo existente', function () {
    $equipo = Equipo::factory()->create(['nombre' => 'Viejo']);

    $this->put(route('equipos.update', $equipo), [
        'nombre' => 'Nuevo nombre',
        'ip' => $equipo->ip,
        'puerto' => $equipo->puerto,
        'comm_key' => $equipo->comm_key,
        'activo' => '1',
    ])->assertRedirect(route('equipos.index'));

    expect($equipo->refresh()->nombre)->toBe('Nuevo nombre');
});

test('elimina un equipo', function () {
    $equipo = Equipo::factory()->create();

    $this->delete(route('equipos.destroy', $equipo))
        ->assertRedirect(route('equipos.index'));

    $this->assertDatabaseMissing('equipos', ['id' => $equipo->id]);
});

test('un invitado no puede entrar al listado', function () {
    auth()->logout();

    $this->get(route('equipos.index'))->assertRedirect();
});

test('un usuario sin permiso no puede entrar al listado', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('equipos.index'))->assertForbidden();
});

test('un rol con permisos de Usuarios pero no de Equipos no puede entrar', function () {
    Permission::firstOrCreate(['name' => 'ViewAny:User', 'guard_name' => 'web']);
    $rol = Role::create(['name' => 'solo_usuarios', 'guard_name' => 'web']);
    $rol->givePermissionTo('ViewAny:User');

    $this->actingAs(User::factory()->create()->assignRole($rol));

    $this->get(route('equipos.index'))->assertForbidden();
});

test('probar conexión marca el equipo en línea y guarda el algoritmo', function () {
    Http::fake([
        'microservicio.test/device/info*' => Http::response([
            'en_linea' => true,
            'algoritmo' => 'ZLM60_TFT | Ver 6.60',
        ], 200),
    ]);

    $equipo = Equipo::factory()->create(['en_linea' => false, 'algoritmo' => null]);

    $this->post(route('equipos.probar-conexion', $equipo))
        ->assertRedirect()
        ->assertSessionHas('estado');

    expect($equipo->refresh())
        ->en_linea->toBeTrue()
        ->algoritmo->toBe('ZLM60_TFT | Ver 6.60');

    expect($equipo->ultima_sync)->not->toBeNull();
});

test('probar conexión marca fuera de línea si el equipo no responde', function () {
    Http::fake([
        'microservicio.test/device/info*' => Http::response([
            'detail' => 'No se pudo conectar con el equipo 192.168.1.201:4370',
        ], 503),
    ]);

    $equipo = Equipo::factory()->create(['en_linea' => true]);

    $this->post(route('equipos.probar-conexion', $equipo))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($equipo->refresh()->en_linea)->toBeFalse();
});

test('descarga dos veces sin pegarle de nuevo al equipo, gracias a la caché', function () {
    Http::fake([
        'microservicio.test/device/attendance*' => Http::response([
            'marcaciones' => [
                ['uid' => 1, 'user_id' => '1', 'nombre' => 'Empleado', 'timestamp' => '2026-07-10T08:00:00'],
            ],
        ], 200),
    ]);

    $equipo = Equipo::factory()->create();

    $this->get(route('equipos.marcaciones.exportar', $equipo))->assertOk();
    $this->get(route('equipos.marcaciones.exportar', ['equipo' => $equipo, 'desde' => '2026-07-05', 'hasta' => '2026-07-15']))->assertOk();

    Http::assertSentCount(1);
});

test('descarga las marcaciones del equipo en CSV', function () {
    Http::fake([
        'microservicio.test/device/attendance*' => Http::response([
            'marcaciones' => [
                ['uid' => 1, 'user_id' => '7633685', 'nombre' => 'Ignacio Molina Guzman', 'timestamp' => '2026-07-09T21:05:48'],
            ],
        ], 200),
    ]);

    $equipo = Equipo::factory()->create(['nombre' => 'iClock Prueba']);

    $response = $this->get(route('equipos.marcaciones.exportar', $equipo))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->headers->get('content-disposition'))->toContain('marcaciones-iclock-prueba-');
    expect($response->getContent())
        ->toContain('CI/ID,Nombre,Fecha,Hora')
        ->toContain('7633685,"Ignacio Molina Guzman",09/07/2026,21:05:48');
});

test('exporta el csv filtrado por rango de fechas', function () {
    Http::fake([
        'microservicio.test/device/attendance*' => Http::response([
            'marcaciones' => [
                ['uid' => 1, 'user_id' => '1', 'nombre' => 'Dentro del rango', 'timestamp' => '2026-07-10T08:00:00'],
                ['uid' => 2, 'user_id' => '2', 'nombre' => 'Fuera del rango', 'timestamp' => '2026-07-20T08:00:00'],
            ],
        ], 200),
    ]);

    $equipo = Equipo::factory()->create();

    $response = $this->get(route('equipos.marcaciones.exportar', ['equipo' => $equipo, 'desde' => '2026-07-05', 'hasta' => '2026-07-15']))
        ->assertOk();

    expect($response->getContent())
        ->toContain('Dentro del rango')
        ->not->toContain('Fuera del rango');
});

test('la descarga CSV redirige con error si el equipo no responde', function () {
    Http::fake([
        'microservicio.test/device/attendance*' => Http::response([
            'detail' => 'No se pudo conectar con el equipo',
        ], 503),
    ]);

    $equipo = Equipo::factory()->create();

    $this->get(route('equipos.marcaciones.exportar', $equipo))
        ->assertRedirect()
        ->assertSessionHas('error', 'No se pudo conectar con el equipo');
});

test('sincroniza las marcaciones del equipo directo a la BD del SIA', function () {
    fakeSiaDatabase();
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '7633685', 'Paterno' => 'Molina', 'Materno' => null, 'Nombres' => 'Ignacio', 'PinReloj' => '7633685', 'MarcaDirecta' => false,
    ]);

    Http::fake([
        'microservicio.test/device/attendance*' => Http::response([
            'marcaciones' => [
                ['uid' => 1, 'user_id' => '7633685', 'nombre' => 'NN', 'timestamp' => '2026-07-10T08:00:00'],
            ],
        ], 200),
    ]);

    $equipo = Equipo::factory()->create();

    $this->post(route('equipos.marcaciones.sincronizar', $equipo), ['desde' => '2026-07-01', 'hasta' => '2026-07-15'])
        ->assertRedirect()
        ->assertSessionHas('estado', fn (string $mensaje) => str_contains($mensaje, '1 marcación(es) nueva(s)'));

    expect(DB::connection('sia')->table('Asistencia')->where('IdPersona', '7633685')->count())->toBe(1);
});

test('la sincronización a la BD respeta el rango de fechas', function () {
    fakeSiaDatabase();
    DB::connection('sia')->table('Personas')->insert([
        'IdPersona' => '5', 'Paterno' => 'Test', 'Materno' => null, 'Nombres' => 'Uno', 'PinReloj' => '5', 'MarcaDirecta' => false,
    ]);

    Http::fake([
        'microservicio.test/device/attendance*' => Http::response([
            'marcaciones' => [
                ['uid' => 1, 'user_id' => '5', 'nombre' => 'NN', 'timestamp' => '2026-07-10T08:00:00'],
                ['uid' => 2, 'user_id' => '5', 'nombre' => 'NN', 'timestamp' => '2026-07-20T08:00:00'],
            ],
        ], 200),
    ]);

    $equipo = Equipo::factory()->create();

    $this->post(route('equipos.marcaciones.sincronizar', $equipo), ['desde' => '2026-07-01', 'hasta' => '2026-07-15'])
        ->assertRedirect();

    expect(DB::connection('sia')->table('Asistencia')->where('IdPersona', '5')->count())->toBe(1);
});

test('la sincronización redirige con error si el equipo no responde', function () {
    Http::fake([
        'microservicio.test/device/attendance*' => Http::response(['detail' => 'No se pudo conectar con el equipo'], 503),
    ]);

    $equipo = Equipo::factory()->create();

    $this->post(route('equipos.marcaciones.sincronizar', $equipo))
        ->assertRedirect()
        ->assertSessionHas('error', 'No se pudo conectar con el equipo');
});

test('un usuario sin permiso no puede sincronizar a la BD', function () {
    $this->actingAs(User::factory()->create());

    $equipo = Equipo::factory()->create();

    $this->post(route('equipos.marcaciones.sincronizar', $equipo))->assertForbidden();
});

test('el modal del listado ofrece descargar y enviar a la BD por rango', function () {
    $equipo = Equipo::factory()->create();

    $this->get(route('equipos.index'))
        ->assertOk()
        ->assertSee(route('equipos.marcaciones.sincronizar', $equipo), escape: false)
        ->assertSee('Enviar a la BD');
});
