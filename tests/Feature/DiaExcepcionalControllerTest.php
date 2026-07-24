<?php

use App\Models\DiaExcepcional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

test('el listado muestra los días excepcionales', function () {
    DiaExcepcional::factory()->create([
        'fecha' => '2025-01-01 00:00:00',
        'motivoInasistencia' => 'AÑO NUEVO',
    ]);

    $this->get(route('dias-excepcionales.index'))
        ->assertOk()
        ->assertSee('AÑO NUEVO')
        ->assertSee('01/01/2025');
});

test('la búsqueda filtra por motivo', function () {
    DiaExcepcional::factory()->create(['fecha' => '2025-03-04 00:00:00', 'motivoInasistencia' => 'FERIADO POR CARNAVAL']);
    DiaExcepcional::factory()->create(['fecha' => '2025-12-25 00:00:00', 'motivoInasistencia' => 'NAVIDAD']);

    $this->get(route('dias-excepcionales.index', ['q' => 'carnaval']))
        ->assertOk()
        ->assertSee('FERIADO POR CARNAVAL')
        ->assertDontSee('NAVIDAD');
});

test('la búsqueda filtra por fecha', function () {
    DiaExcepcional::factory()->create(['fecha' => '2025-05-01 00:00:00', 'motivoInasistencia' => 'DIA DEL TRABAJO']);
    DiaExcepcional::factory()->create(['fecha' => '2024-11-18 00:00:00', 'motivoInasistencia' => 'ANIVERSARIO DEL BENI']);

    $this->get(route('dias-excepcionales.index', ['q' => '01/05/2025']))
        ->assertOk()
        ->assertSee('DIA DEL TRABAJO')
        ->assertDontSee('ANIVERSARIO DEL BENI');

    $this->get(route('dias-excepcionales.index', ['q' => '2024']))
        ->assertOk()
        ->assertSee('ANIVERSARIO DEL BENI')
        ->assertDontSee('DIA DEL TRABAJO');
});

test('registra un día excepcional nuevo', function () {
    $this->post(route('dias-excepcionales.store'), [
        'fecha' => '2025-03-04',
        'motivoInasistencia' => 'FERIADO POR CARNAVAL',
    ])
        ->assertRedirect(route('dias-excepcionales.index'))
        ->assertSessionHas('estado');

    $dia = DiaExcepcional::query()->first();

    expect($dia)->not->toBeNull()
        ->and($dia->motivoInasistencia)->toBe('FERIADO POR CARNAVAL')
        ->and($dia->fecha->format('Y-m-d'))->toBe('2025-03-04');
});

test('el alta valida fecha y motivo obligatorios', function () {
    $this->post(route('dias-excepcionales.store'), [
        'fecha' => '',
        'motivoInasistencia' => '',
    ])->assertSessionHasErrors(['fecha', 'motivoInasistencia']);

    expect(DiaExcepcional::query()->count())->toBe(0);
});

test('el alta rechaza una fecha repetida', function () {
    DiaExcepcional::factory()->create(['fecha' => '2025-05-01 00:00:00']);

    $this->post(route('dias-excepcionales.store'), [
        'fecha' => '2025-05-01',
        'motivoInasistencia' => 'DÍA DEL TRABAJO',
    ])->assertSessionHasErrors('fecha');

    expect(DiaExcepcional::query()->count())->toBe(1);
});

test('muestra el formulario de edición con los datos actuales', function () {
    $dia = DiaExcepcional::factory()->create([
        'fecha' => '2024-12-25 00:00:00',
        'motivoInasistencia' => 'NAVIDAD',
    ]);

    $this->get(route('dias-excepcionales.edit', $dia))
        ->assertOk()
        ->assertSee('NAVIDAD')
        ->assertSee('2024-12-25');
});

test('actualiza un día excepcional', function () {
    $dia = DiaExcepcional::factory()->create([
        'fecha' => '2024-11-18 00:00:00',
        'motivoInasistencia' => 'ANIVERSARIO',
    ]);

    $this->put(route('dias-excepcionales.update', $dia), [
        'fecha' => '2024-11-18',
        'motivoInasistencia' => 'ANIVERSARIO DEL BENI',
    ])
        ->assertRedirect(route('dias-excepcionales.index'))
        ->assertSessionHas('estado');

    expect($dia->refresh()->motivoInasistencia)->toBe('ANIVERSARIO DEL BENI');
});

test('elimina un día excepcional de forma lógica y registra quién lo borró', function () {
    $admin = asSuperAdmin();
    $this->actingAs($admin);

    $dia = DiaExcepcional::factory()->create();

    $this->delete(route('dias-excepcionales.destroy', $dia))
        ->assertRedirect(route('dias-excepcionales.index'));

    expect(DiaExcepcional::query()->whereKey($dia->getKey())->exists())->toBeFalse();

    $borrado = DiaExcepcional::onlyTrashed()->find($dia->getKey());

    expect($borrado)->not->toBeNull()
        ->and($borrado->deleteUser_id)->toBe($admin->id);
});

test('un invitado no puede ver los días excepcionales', function () {
    auth()->logout();

    $this->get(route('dias-excepcionales.index'))->assertRedirect();
});

test('un usuario sin permiso no puede entrar al listado', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dias-excepcionales.index'))->assertForbidden();
});
