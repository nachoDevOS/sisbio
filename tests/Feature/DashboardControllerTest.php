<?php

use App\Models\Equipo;
use App\Models\Sia\Asistencia;
use App\Models\Sia\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    fakeSiaDatabase();
    $this->actingAs(asSuperAdmin());
});

test('muestra los conteos de equipos', function () {
    Equipo::factory()->create(['en_linea' => true, 'es_master' => true]);
    Equipo::factory()->create(['en_linea' => false]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Equipos registrados')
        ->assertSee('En línea')
        ->assertSee('Fuera de línea')
        ->assertSee('Equipos maestros');
});

test('lista solo los equipos activos fuera de línea', function () {
    Equipo::factory()->create(['nombre' => 'Reloj Caído', 'en_linea' => false, 'activo' => true]);
    Equipo::factory()->create(['nombre' => 'Reloj Sano', 'en_linea' => true, 'activo' => true]);
    Equipo::factory()->create(['nombre' => 'Reloj Retirado', 'en_linea' => false, 'activo' => false]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Reloj Caído')
        ->assertDontSee('Reloj Sano')
        ->assertDontSee('Reloj Retirado');
});

test('muestra el estado vacío cuando no hay equipos fuera de línea', function () {
    Equipo::factory()->create(['en_linea' => true, 'activo' => true]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Todos los equipos están en línea');
});

test('muestra los conteos de asistencia del SIA', function () {
    $persona = Persona::factory()->create();

    Asistencia::factory()->count(3)->create([
        'IdPersona' => $persona->IdPersona,
        'Fecha' => today(),
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Marcaciones hoy')
        ->assertSee('Funcionarios registrados');
});

test('el gráfico dibuja 14 barras, una por día', function () {
    $response = $this->get(route('dashboard'))->assertOk();

    expect(substr_count($response->getContent(), 'class="mini-chart__barra"'))->toBe(14);
});

test('un invitado es redirigido al intentar ver el escritorio', function () {
    auth()->logout();

    $this->get(route('dashboard'))->assertRedirect();
});
