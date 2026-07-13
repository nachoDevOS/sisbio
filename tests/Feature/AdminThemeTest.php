<?php

test('el login del panel incluye el tema visual SISCOR', function () {
    $response = $this->get('/admin/login');

    $response->assertSuccessful();
    $response->assertSee('--siscor-green', escape: false);
});

test('la marca del panel muestra el ícono junto al nombre de la app', function () {
    $response = $this->get('/admin/login');

    $response->assertSuccessful();
    $response->assertSee('image/icon.png');
    $response->assertSee(config('app.name'));
});

test('el panel registra notificaciones de error en español', function () {
    $titulos = collect(Filament\Facades\Filament::getPanel('admin')->getErrorNotifications())
        ->pluck('title');

    expect($titulos)->toContain('Ocurrió un error')
        ->toContain('Registro no encontrado');
});

test('el tema muestra el logo en el sidebar y lo oculta del topbar en escritorio', function () {
    $css = view('filament.theme')->render();

    expect($css)
        ->toContain('.fi-body-has-topbar .fi-sidebar.fi-sidebar-open .fi-sidebar-header')
        ->toContain('.fi-body-has-topbar:has(.fi-sidebar.fi-sidebar-open) .fi-topbar .fi-logo')
        ->toContain('--siscor-topbar-h');
});

test('el tema estiliza la paginación de las tablas', function () {
    $css = view('filament.theme')->render();

    expect($css)
        ->toContain('.fi-pagination-item.fi-active .fi-pagination-item-btn')
        ->toContain('.fi-ta-row.fi-striped')
        ->toContain('.fi-pagination .fi-pagination-items')
        ->toContain('.fi-header-heading');
});
