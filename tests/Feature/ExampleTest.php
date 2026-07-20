<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('la raíz muestra el escritorio a un usuario logueado', function () {
    $this->actingAs(asSuperAdmin());

    $this->get('/')
        ->assertOk()
        ->assertSee('Escritorio');
});

test('la raíz redirige a un invitado', function () {
    $this->get('/')->assertRedirect();
});
