<?php

use App\Models\Equipo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

test('el listado muestra los equipos registrados', function () {
    $equipo = Equipo::factory()->create(['nombre' => 'iClock Entrada']);

    $this->get(route('equipos.index'))
        ->assertOk()
        ->assertSee('iClock Entrada');
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
