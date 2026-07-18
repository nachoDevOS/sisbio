<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

test('el listado muestra los usuarios', function () {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);

    $this->get(route('usuarios.index'))
        ->assertOk()
        ->assertSee('Ada Lovelace');
});

test('muestra el formulario de alta', function () {
    $this->get(route('usuarios.create'))
        ->assertOk()
        ->assertSee('Nuevo usuario');
});

test('crea un usuario, hashea la contraseña y asigna roles', function () {
    $rol = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);

    $this->post(route('usuarios.store'), [
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
        'password' => 'secret123',
        'roles' => [$rol->id],
    ])->assertRedirect(route('usuarios.index'));

    $user = User::where('email', 'grace@example.com')->firstOrFail();

    expect(Hash::check('secret123', $user->password))->toBeTrue();
    expect($user->hasRole('operador'))->toBeTrue();
});

test('rechaza correo duplicado', function () {
    User::factory()->create(['email' => 'dup@example.com']);

    $this->post(route('usuarios.store'), [
        'name' => 'Otro',
        'email' => 'dup@example.com',
        'password' => 'secret123',
    ])->assertSessionHasErrors('email');
});

test('rechaza contraseña corta al crear', function () {
    $this->post(route('usuarios.store'), [
        'name' => 'Corto',
        'email' => 'corto@example.com',
        'password' => '123',
    ])->assertSessionHasErrors('password');
});

test('muestra el formulario de edición con los datos actuales', function () {
    $user = User::factory()->create(['name' => 'Editar Nombre']);

    $this->get(route('usuarios.edit', $user))
        ->assertOk()
        ->assertSee('Editar Nombre');
});

test('actualiza sin cambiar la contraseña si viene vacía', function () {
    $user = User::factory()->create(['password' => Hash::make('original1')]);
    $passwordAntes = $user->password;

    $this->put(route('usuarios.update', $user), [
        'name' => 'Nombre nuevo',
        'email' => $user->email,
        'password' => '',
    ])->assertRedirect(route('usuarios.index'));

    $user->refresh();
    expect($user->name)->toBe('Nombre nuevo');
    expect($user->password)->toBe($passwordAntes);
});

test('actualiza la contraseña cuando se envía una nueva', function () {
    $user = User::factory()->create();

    $this->put(route('usuarios.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'nuevaclave1',
    ])->assertRedirect(route('usuarios.index'));

    expect(Hash::check('nuevaclave1', $user->refresh()->password))->toBeTrue();
});

test('elimina un usuario', function () {
    $user = User::factory()->create();

    $this->delete(route('usuarios.destroy', $user))
        ->assertRedirect(route('usuarios.index'));

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});
