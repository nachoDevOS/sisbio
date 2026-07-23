<?php

use App\Models\Sia\DiaTurno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    fakeSiaDatabase();
    $this->actingAs(asSuperAdmin());
});

function datosDeHorario(array $sobrescribir = []): array
{
    return array_merge([
        'Dia' => '2',
        'NombreTurno' => 'Turno de prueba',
        'HEntrada' => '08:00',
        'HTolerancia' => '08:10',
        'EMinima' => '07:00',
        'EMaxima' => '10:00',
        'HSalida' => '16:00',
        'STolerancia' => '16:00',
        'SMinima' => '16:00',
        'SMaxima' => '23:59',
        'HTrabajadas' => '8.00',
        'SiguienteDia' => '1',
    ], $sobrescribir);
}

test('el listado muestra los horarios registrados', function () {
    DiaTurno::factory()->create(['NombreTurno' => 'Turno Mañana']);

    $this->get(route('horarios.index'))
        ->assertOk()
        ->assertSee('Turno Mañana');
});

test('muestra el formulario de alta', function () {
    $this->get(route('horarios.create'))
        ->assertOk()
        ->assertSee('Nuevo horario');
});

test('muestra la ficha de un horario', function () {
    $horario = DiaTurno::factory()->create(['Dia' => '2', 'NombreTurno' => 'LUN: 08:00 - 16:00']);

    $this->get(route('horarios.show', $horario))
        ->assertOk()
        ->assertSee('LUN: 08:00 - 16:00')
        ->assertSee('Lunes')
        ->assertSee('Entrada')
        ->assertSee('Salida');
});

test('guarda un horario nuevo con código autogenerado y redirige al listado', function () {
    $this->post(route('horarios.store'), datosDeHorario())
        ->assertRedirect(route('horarios.index'))
        ->assertSessionHas('estado');

    $this->assertDatabaseHas('DiaTurnos', [
        'Dia' => '2',
        'NombreTurno' => 'Turno de prueba',
    ], 'sia');

    $horario = DiaTurno::query()->first();
    expect(trim($horario->IdTurno))->toHaveLength(3)
        ->and($horario->HEntrada->format('H:i'))->toBe('08:00')
        ->and($horario->SiguienteDia)->toBeTrue();
});

test('el alta valida los campos obligatorios', function () {
    $this->post(route('horarios.store'), [])
        ->assertSessionHasErrors(['Dia', 'NombreTurno', 'HEntrada', 'HSalida', 'HTrabajadas']);

    $this->assertDatabaseCount('DiaTurnos', 0, 'sia');
});

test('el alta rechaza una hora mal formada', function () {
    $this->post(route('horarios.store'), datosDeHorario(['HEntrada' => '25:99']))
        ->assertSessionHasErrors('HEntrada');
});

test('el listado filtra por nombre del horario', function () {
    DiaTurno::factory()->create(['NombreTurno' => 'LUN: 08:00 - 16:00']);
    DiaTurno::factory()->create(['NombreTurno' => 'MAR: 14:00 - 22:00']);

    $this->get(route('horarios.index', ['buscar' => '08:00']))
        ->assertOk()
        ->assertSee('LUN: 08:00 - 16:00')
        ->assertDontSee('MAR: 14:00 - 22:00');
});

test('el listado filtra por día', function () {
    DiaTurno::factory()->create(['Dia' => '2', 'NombreTurno' => 'Turno del lunes']);
    DiaTurno::factory()->create(['Dia' => '3', 'NombreTurno' => 'Turno del martes']);

    $this->get(route('horarios.index', ['dia' => '2']))
        ->assertOk()
        ->assertSee('Turno del lunes')
        ->assertDontSee('Turno del martes');
});

test('muestra el formulario de edición con los datos actuales', function () {
    $horario = DiaTurno::factory()->create(['NombreTurno' => 'Turno Tarde']);

    $this->get(route('horarios.edit', $horario))
        ->assertOk()
        ->assertSee('Turno Tarde');
});

test('actualiza un horario existente', function () {
    $horario = DiaTurno::factory()->create(['NombreTurno' => 'Viejo']);

    $this->put(route('horarios.update', $horario), datosDeHorario(['NombreTurno' => 'Nuevo nombre']))
        ->assertRedirect(route('horarios.index'));

    expect(trim($horario->refresh()->NombreTurno))->toBe('Nuevo nombre');
});

test('elimina un horario', function () {
    $horario = DiaTurno::factory()->create();

    $this->delete(route('horarios.destroy', $horario))
        ->assertRedirect(route('horarios.index'));

    $this->assertDatabaseCount('DiaTurnos', 0, 'sia');
});

test('un invitado no puede entrar al listado', function () {
    auth()->logout();

    $this->get(route('horarios.index'))->assertRedirect();
});

test('un usuario sin permiso no puede entrar al listado', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('horarios.index'))->assertForbidden();
});
