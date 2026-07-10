<?php

use App\Filament\Resources\Equipos\EquipoResource;
use App\Filament\Resources\Equipos\Pages\CreateEquipo;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Models\Equipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

test('al crear un equipo redirige al listado', function () {
    Livewire::test(CreateEquipo::class)
        ->fillForm([
            'nombre' => 'iClock Entrada',
            'ip' => '192.168.1.50',
            'puerto' => 4370,
            'comm_key' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(EquipoResource::getUrl('index'));

    $this->assertDatabaseHas('equipos', ['nombre' => 'iClock Entrada', 'ip' => '192.168.1.50']);
});

test('al editar un equipo redirige al listado', function () {
    $equipo = Equipo::factory()->create();

    Livewire::test(EditEquipo::class, ['record' => $equipo->getRouteKey()])
        ->fillForm(['nombre' => 'Nombre actualizado'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(EquipoResource::getUrl('index'));

    expect($equipo->refresh()->nombre)->toBe('Nombre actualizado');
});
