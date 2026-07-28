<?php

use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('la baja del sitio guarda quién eliminó y por qué, sin tocar el controlador', function () {
    $admin = asSuperAdmin();
    $this->actingAs($admin);

    $horario = Turno::factory()->create();

    $this->delete(route('horarios.destroy', $horario), ['deleteObservacion' => 'Turno cargado por error.'])
        ->assertRedirect(route('horarios.index'));

    $borrado = Turno::onlyTrashed()->find($horario->getKey());

    expect($borrado)->not->toBeNull()
        ->and($borrado->deleteUser_id)->toBe($admin->id)
        ->and($borrado->deleteObservacion)->toBe('Turno cargado por error.');
});

test('sin motivo la baja igual se hace, pero queda sin explicación', function () {
    $admin = asSuperAdmin();
    $this->actingAs($admin);

    $horario = Turno::factory()->create();

    // El motivo lo exige el modal (required/minlength); el trait solo guarda lo
    // que llegue. Un DELETE armado a mano pasa igual, con la columna en null.
    $this->delete(route('horarios.destroy', $horario))
        ->assertRedirect(route('horarios.index'));

    $borrado = Turno::onlyTrashed()->find($horario->getKey());

    expect($borrado)->not->toBeNull()
        ->and($borrado->deleteUser_id)->toBe($admin->id)
        ->and($borrado->deleteObservacion)->toBeNull();
});

test('un borrado interno sigue guardando quién lo hizo', function () {
    $admin = asSuperAdmin();
    $this->actingAs($admin);

    // Reusar una fila, limpiar en cascada, un comando o un seeder borran sin
    // que haya nadie escribiendo un motivo: eso tiene que seguir funcionando.
    $horario = Turno::factory()->create();
    $horario->delete();

    $borrado = Turno::onlyTrashed()->find($horario->getKey());

    expect($borrado)->not->toBeNull()
        ->and($borrado->deleteUser_id)->toBe($admin->id)
        ->and($borrado->deleteObservacion)->toBeNull();
});

test('sin usuario en sesión no se escribe auditoría de baja', function () {
    $horario = Turno::factory()->create();

    $horario->delete();

    $borrado = Turno::onlyTrashed()->find($horario->getKey());

    expect($borrado)->not->toBeNull()
        ->and($borrado->deleteUser_id)->toBeNull()
        ->and($borrado->deleteObservacion)->toBeNull();
});

test('el alta sigue registrando quién la hizo', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $horario = Turno::factory()->create();

    expect($horario->refresh()->registerUser_id)->toBe($admin->id);
});
