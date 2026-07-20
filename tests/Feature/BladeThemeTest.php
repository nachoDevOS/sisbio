<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

test('el sitio Blade usa la misma paleta petróleo que el panel Filament', function () {
    $response = $this->get(route('equipos.index'));

    $response->assertSuccessful();
    $response->assertSee('--sidebar: #0d3b3e', escape: false);
    $response->assertSee('--sidebar-header: #082628', escape: false);
    $response->assertSee('--sidebar-hover: #164e52', escape: false);
    $response->assertSee('--sidebar-activo: #1c6266', escape: false);
    $response->assertSee('--thead: #0d3b3e', escape: false);
});
