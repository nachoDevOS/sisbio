<?php

use App\Filament\Widgets\SiaAsistenciaStats;
use App\Filament\Widgets\SiaMarcacionesChart;
use App\Models\Sia\Asistencia;
use App\Models\Sia\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    fakeSiaDatabase();
    $this->actingAs(asSuperAdmin());
});

test('las tarjetas de asistencia muestran los conteos del SIA', function () {
    $persona = Persona::factory()->create();

    Asistencia::factory()->count(3)->create([
        'IdPersona' => $persona->IdPersona,
        'Fecha' => today(),
    ]);

    Livewire::test(SiaAsistenciaStats::class)
        ->assertSuccessful()
        ->assertSee('Marcaciones hoy')
        ->assertSee('Funcionarios registrados');
});

test('el gráfico arma una serie de 14 días con los totales por fecha', function () {
    $persona = Persona::factory()->create();

    Asistencia::factory()->count(2)->create([
        'IdPersona' => $persona->IdPersona,
        'Fecha' => today(),
    ]);

    $widget = new SiaMarcacionesChart;
    $data = (fn (): array => $this->getData())->call($widget);

    expect($data['labels'])->toHaveCount(14)
        ->and($data['datasets'][0]['data'])->toHaveCount(14)
        ->and(array_sum($data['datasets'][0]['data']))->toBe(2);
});
