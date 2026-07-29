<?php

use App\Models\AsignacionTurno;
use App\Models\Asistencia;
use App\Models\Licencia;
use App\Models\Persona;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

/**
 * Funcionario con el turno LUN 08:00–16:00 asignado y un lunes marcado con
 * atraso: entra 08:25 (tolerancia 08:10) y sale 16:50.
 */
function funcionarioProcesado(): Persona
{
    $hora = fn (string $hm): string => "1899-12-30 {$hm}:00";

    $persona = Persona::factory()->create([
        'ci' => '7633685',
        'paterno' => 'Molina',
        'materno' => 'Guzman',
        'nombres' => 'Ignacio',
        'pinReloj' => '7633685',
    ]);

    $turno = Turno::factory()->create([
        'dia' => '2',
        'nombreTurno' => 'LUN: 08:00 - 16:00',
        'hEntrada' => $hora('08:00'),
        'hTolerancia' => $hora('08:10'),
        'eMinima' => $hora('07:00'),
        'eMaxima' => $hora('09:00'),
        'hSalida' => $hora('16:00'),
        'sTolerancia' => $hora('16:00'),
        'sMinima' => $hora('16:00'),
        'sMaxima' => $hora('20:00'),
        'hTrabajadas' => 8,
        'siguienteDia' => false,
    ]);

    AsignacionTurno::factory()->create([
        'ci' => $persona->ci,
        'turno_id' => $turno->id,
        'desde' => '2026-01-01 00:00:00',
        'hasta' => '2026-12-31 00:00:00',
    ]);

    foreach (['08:25:00', '16:50:00'] as $marca) {
        Asistencia::factory()->create([
            'ci' => $persona->ci,
            'fecha' => '2026-07-27 00:00:00',
            'hora' => "1899-12-30 {$marca}",
        ]);
    }

    return $persona;
}

/**
 * Parámetros del lunes de referencia.
 *
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function rangoProcesado(array $extra = []): array
{
    return array_merge([
        'persona' => '7633685',
        'desde' => '2026-07-27',
        'hasta' => '2026-07-27',
    ], $extra);
}

test('el formulario de selección carga', function () {
    $this->get(route('reportes.marcaciones.procesado'))
        ->assertOk()
        ->assertSee('Marcaciones procesadas');
});

test('generar muestra el día procesado con el atraso y las horas computadas', function () {
    funcionarioProcesado();

    $this->get(route('reportes.marcaciones.procesado.generar', rangoProcesado()))
        ->assertOk()
        // Columnas del reporte del escritorio viejo, con «Día» sumado adelante.
        ->assertSeeInOrder(['Fecha', 'Día', 'Turno', 'Entró', 'Salió', 'Atraso', 'Abandono', 'Falta',
            'Entrada lic.', 'Salida lic.', 'T.C.', 'C.G.H.', 'Motivo licencia'])
        ->assertSee('LUN: 08:00 - 16:00')
        ->assertSee('08:25:00')
        ->assertSee('16:50:00')
        ->assertSee('25 min')
        // 16:00 − 08:25, acotado al turno: va en el resumen, no en la fila.
        ->assertSee('7h 35m');
});

test('la licencia por horas llena las columnas de licencia', function () {
    $persona = funcionarioProcesado();

    Licencia::factory()->porHoras('08:00', '11:00')->create([
        'ci' => $persona->ci,
        'fecha' => '2026-07-27 00:00:00',
        'turno_id' => Turno::query()->value('id'),
        'goceHaberes' => false,
        'motivo' => 'BAJA MEDICA',
    ]);

    $this->get(route('reportes.marcaciones.procesado.generar', rangoProcesado()))
        ->assertOk()
        ->assertSee('08:00')
        ->assertSee('11:00')
        ->assertSee('BAJA MEDICA')
        // T.C. = No (es por horas), C.G.H. = No (sin goce).
        ->assertSee('No');
});

test('la licencia de turno completo muestra el turno con T.C. en sí', function () {
    $persona = funcionarioProcesado();

    Licencia::factory()->create([
        'ci' => $persona->ci,
        'fecha' => '2026-07-27 00:00:00',
        'turno_id' => Turno::query()->value('id'),
        'tCompleto' => true,
        'goceHaberes' => true,
        'motivo' => 'VACACION',
    ]);

    $this->get(route('reportes.marcaciones.procesado.generar', rangoProcesado()))
        ->assertOk()
        ->assertSee('LUN: 08:00 - 16:00')
        ->assertSee('VACACION')
        ->assertSee('Sí')
        // La licencia manda: no se evalúan las marcas del día.
        ->assertDontSee('25 min');
});

test('el rango invertido se da vuelta en vez de salir vacío', function () {
    funcionarioProcesado();

    $this->get(route('reportes.marcaciones.procesado.generar', [
        'persona' => '7633685',
        'desde' => '2026-07-31',
        'hasta' => '2026-07-27',
    ]))
        ->assertOk()
        ->assertSee('LUN: 08:00 - 16:00');
});

test('el día sin turno asignado se informa como no laborable', function () {
    funcionarioProcesado();

    // Domingo: el turno es de los lunes.
    $this->get(route('reportes.marcaciones.procesado.generar', rangoProcesado([
        'desde' => '2026-07-26', 'hasta' => '2026-07-26',
    ])))
        ->assertOk()
        ->assertSee('No laborable');
});

test('generar con print=1 devuelve la versión imprimible', function () {
    funcionarioProcesado();

    $this->get(route('reportes.marcaciones.procesado.generar', rangoProcesado(['print' => 1])))
        ->assertOk()
        ->assertSee('REPORTE DE MARCACIONES')
        ->assertSee('Marcaciones procesadas')
        ->assertSeeText('PIN Reloj: 7633685')
        ->assertSee('Totales del rango:')
        ->assertSee('T.C.')
        ->assertSee('C.G.H.')
        ->assertSee('7h 35m');
});

test('generar con print=2 descarga el CSV', function () {
    funcionarioProcesado();

    $response = $this->get(route('reportes.marcaciones.procesado.generar', rangoProcesado(['print' => 2])))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->getContent())
        ->toContain('Fecha,Dia,Turno,Entro,Salio,Atraso,Abandono,Falta,Entrada lic.,Salida lic.,T.C.,C.G.H.')
        ->toContain('"08:25:00"')
        ->toContain('"25 min"')
        ->toContain('"Atraso"');
});

test('generar sin funcionario vuelve al formulario con error', function () {
    $this->get(route('reportes.marcaciones.procesado.generar'))
        ->assertRedirect(route('reportes.marcaciones.procesado'))
        ->assertSessionHas('error');
});

test('un usuario sin permiso no puede entrar', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('reportes.marcaciones.procesado'))->assertForbidden();
});
