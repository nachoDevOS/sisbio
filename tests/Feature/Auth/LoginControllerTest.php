<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('muestra el formulario de login', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Entrar');
});

test('inicia sesión con credenciales correctas', function () {
    $usuario = User::factory()->create(['password' => Hash::make('clave-correcta')]);

    $this->post(route('login'), [
        'email' => $usuario->email,
        'password' => 'clave-correcta',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($usuario);
});

test('rechaza credenciales incorrectas', function () {
    $usuario = User::factory()->create(['password' => Hash::make('clave-correcta')]);

    $this->post(route('login'), [
        'email' => $usuario->email,
        'password' => 'clave-mala',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('cierra sesión', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('un usuario logueado no puede ver el formulario de login', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('login'))->assertRedirect();
});
