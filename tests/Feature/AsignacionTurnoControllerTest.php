<?php

use App\Models\AsignacionTurno;
use App\Models\Persona;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

test('la pantalla carga el shell del listado', function () {
    $this->get(route('turnos-asignados.index'))
        ->assertOk()
        ->assertSee('Turnos asignados')
        ->assertSee('id="div-results"', false);
});

test('el listado muestra el funcionario y su turno cruzados por CI y turno_id', function () {
    Persona::factory()->create(['ci' => '7633685', 'paterno' => 'Molina', 'nombres' => 'Ignacio']);
    $turno = Turno::factory()->create(['dia' => '2', 'nombreTurno' => 'LUN: 08:00 - 16:00']);

    AsignacionTurno::factory()->create(['ci' => '7633685', 'turno_id' => $turno->id]);

    $this->get(route('turnos-asignados.list'))
        ->assertOk()
        ->assertSee('Ignacio Molina')
        ->assertSee('7633685')
        ->assertSee('LUN: 08:00 - 16:00')
        ->assertSee('Lunes')
        ->assertSee('Vigente');
});

test('el listado avisa cuando la asignación quedó sin turno vinculado', function () {
    // La copia del SIA deja `turno_id` en null si el código histórico no cruzó.
    AsignacionTurno::factory()->create(['ci' => '7633685', 'turno_id' => null, 'idTurno' => '999']);

    $this->get(route('turnos-asignados.list'))
        ->assertOk()
        ->assertSee('Sin turno vinculado');
});

test('la búsqueda filtra por CI, por nombre del funcionario y por nombre del turno', function () {
    Persona::factory()->create(['ci' => '1111111', 'paterno' => 'Molina', 'nombres' => 'Ignacio']);
    Persona::factory()->create(['ci' => '2222222', 'paterno' => 'Perez', 'nombres' => 'Juana']);

    $manana = Turno::factory()->create(['nombreTurno' => 'LUN: 08:00 - 16:00']);
    $tarde = Turno::factory()->create(['nombreTurno' => 'MAR: 14:00 - 22:00']);

    AsignacionTurno::factory()->create(['ci' => '1111111', 'turno_id' => $manana->id]);
    AsignacionTurno::factory()->create(['ci' => '2222222', 'turno_id' => $tarde->id]);

    $this->get(route('turnos-asignados.list', ['q' => '1111111']))
        ->assertOk()
        ->assertSee('1111111')
        ->assertDontSee('2222222');

    $this->get(route('turnos-asignados.list', ['q' => 'Molina']))
        ->assertOk()
        ->assertSee('1111111')
        ->assertDontSee('2222222');

    $this->get(route('turnos-asignados.list', ['q' => '14:00']))
        ->assertOk()
        ->assertSee('2222222')
        ->assertDontSee('1111111');
});

test('el listado filtra por día del turno', function () {
    $lunes = Turno::factory()->create(['dia' => '2', 'nombreTurno' => 'Turno del lunes']);
    $martes = Turno::factory()->create(['dia' => '3', 'nombreTurno' => 'Turno del martes']);

    AsignacionTurno::factory()->create(['ci' => '1111111', 'turno_id' => $lunes->id]);
    AsignacionTurno::factory()->create(['ci' => '2222222', 'turno_id' => $martes->id]);

    $this->get(route('turnos-asignados.list', ['dia' => '2']))
        ->assertOk()
        ->assertSee('Turno del lunes')
        ->assertDontSee('Turno del martes');
});

test('el listado filtra por situación respecto de hoy', function () {
    $turno = Turno::factory()->create();

    AsignacionTurno::factory()->create(['ci' => '1111111', 'turno_id' => $turno->id]);
    AsignacionTurno::factory()->vencida()->create(['ci' => '2222222', 'turno_id' => $turno->id]);
    AsignacionTurno::factory()->create([
        'ci' => '3333333',
        'turno_id' => $turno->id,
        'desde' => today()->addMonth(),
        'hasta' => today()->addYear(),
    ]);

    $this->get(route('turnos-asignados.list', ['situacion' => 'vigentes']))
        ->assertOk()
        ->assertSee('1111111')
        ->assertDontSee('2222222')
        ->assertDontSee('3333333');

    $this->get(route('turnos-asignados.list', ['situacion' => 'vencidas']))
        ->assertOk()
        ->assertSee('2222222')
        ->assertSee('Vencida')
        ->assertDontSee('1111111');

    $this->get(route('turnos-asignados.list', ['situacion' => 'futuras']))
        ->assertOk()
        ->assertSee('3333333')
        ->assertSee('Aún no vigente')
        ->assertDontSee('1111111');
});

test('una situación desconocida no deja la tabla vacía', function () {
    $turno = Turno::factory()->create();
    AsignacionTurno::factory()->create(['ci' => '1111111', 'turno_id' => $turno->id]);

    $this->get(route('turnos-asignados.list', ['situacion' => 'cualquiera']))
        ->assertOk()
        ->assertSee('1111111');
});

test('el listado respeta el selector de registros por página', function () {
    $turno = Turno::factory()->create();
    AsignacionTurno::factory()->count(12)->create(['turno_id' => $turno->id]);

    $this->get(route('turnos-asignados.list', ['por_pagina' => 10]))
        ->assertOk()
        ->assertViewHas('asignaciones', fn ($asignaciones): bool => $asignaciones->count() === 10);
});

test('un invitado no puede ver los turnos asignados', function () {
    auth()->logout();

    $this->get(route('turnos-asignados.index'))->assertRedirect();
});

test('un usuario sin permiso no puede ver los turnos asignados', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('turnos-asignados.index'))->assertForbidden();
    $this->get(route('turnos-asignados.list'))->assertForbidden();
});
