<?php

use App\Filament\Resources\Personas\Pages\ListPersonas;
use App\Models\Sia\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    fakeSiaDatabase();
    $this->actingAs(asSuperAdmin());
});

test('lista los funcionarios del SIA', function () {
    $personas = Persona::factory()->count(3)->create();

    Livewire::test(ListPersonas::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($personas);
});

test('busca funcionarios por apellido', function () {
    Persona::factory()->create(['Paterno' => 'Zabaleta']);
    Persona::factory()->create(['Paterno' => 'Quiroga']);

    Livewire::test(ListPersonas::class)
        ->searchTable('Zabaleta')
        ->assertSee('Zabaleta')
        ->assertDontSee('Quiroga');
});
