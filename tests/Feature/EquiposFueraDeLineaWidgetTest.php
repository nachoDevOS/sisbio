<?php

use App\Filament\Widgets\EquiposFueraDeLinea;
use App\Models\Equipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

test('lista solo los equipos activos fuera de línea', function () {
    $caido = Equipo::factory()->create(['nombre' => 'Reloj Caído', 'en_linea' => false, 'activo' => true]);
    Equipo::factory()->create(['nombre' => 'Reloj Sano', 'en_linea' => true, 'activo' => true]);
    Equipo::factory()->create(['nombre' => 'Reloj Retirado', 'en_linea' => false, 'activo' => false]);

    Livewire::test(EquiposFueraDeLinea::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$caido])
        ->assertSee('Reloj Caído')
        ->assertDontSee('Reloj Sano')
        ->assertDontSee('Reloj Retirado');
});

test('sin equipos caídos muestra el estado vacío positivo', function () {
    Equipo::factory()->create(['en_linea' => true, 'activo' => true]);

    Livewire::test(EquiposFueraDeLinea::class)
        ->assertSuccessful()
        ->assertSee('Todos los equipos están en línea');
});
