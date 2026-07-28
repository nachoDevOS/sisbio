<?php

use App\Models\DiaExcepcional;
use App\Models\Equipo;
use App\Models\Licencia;
use App\Models\Persona;
use App\Models\Role;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

test('el layout trae el modal global de eliminación con su formulario único', function () {
    $this->get(route('equipos.index'))
        ->assertOk()
        ->assertSee("Alpine.store('eliminar'", escape: false)
        ->assertSee('id="form-eliminar-global"', escape: false)
        ->assertSee(':action="$store.eliminar.url"', escape: false)
        // El DELETE sale del formulario del modal, no del botón de la fila.
        ->assertSee('name="_method" value="DELETE"', escape: false)
        ->assertSee('¿Seguro que querés eliminar?')
        // El motivo viaja como deleteObservacion: es lo que lee el trait
        // RegistersUserEvents para guardar por qué se dio la baja.
        ->assertSee('name="deleteObservacion" rows="2" required', escape: false)
        ->assertSee('minlength="5"', escape: false)
        ->assertSee('x-model="$store.eliminar.motivo"', escape: false)
        // La casilla obliga a confirmar antes de que el botón rojo se habilite.
        ->assertSee('<input type="checkbox" required x-model="$store.eliminar.confirmado">', escape: false)
        ->assertSee(':disabled="! $store.eliminar.confirmado || ! $store.eliminar.motivoValido || $store.eliminar.enviando"', escape: false);
});

test('el modal no deja mandar la baja con un motivo demasiado corto', function () {
    $this->get(route('equipos.index'))
        ->assertOk()
        ->assertSee('motivoMinimo: 5', escape: false)
        ->assertSee('return this.motivo.trim().length >= this.motivoMinimo;', escape: false);
});

test('si el servidor rechaza el motivo, el aviso sale por el toaster', function () {
    // Equipos es el módulo que además valida el motivo en el controlador,
    // porque lo copia a la bitácora.
    $equipo = Equipo::factory()->create();

    $this->followingRedirects()
        ->from(route('equipos.index'))
        ->delete(route('equipos.destroy', $equipo))
        ->assertOk()
        ->assertSee('class="toast toast--error"', escape: false)
        ->assertSee('El campo motivo es obligatorio.');
});

test('las bajas de los listados abren el modal global en vez del confirm del navegador', function () {
    Role::create(['name' => 'operador', 'guard_name' => 'web']);
    User::factory()->create(['name' => 'Ignacio Molina']);
    Turno::factory()->create(['nombreTurno' => 'LUN: 08:00 - 16:00']);
    DiaExcepcional::factory()->create();
    Persona::factory()->create(['ci' => '7633685', 'nombres' => 'IGNACIO', 'paterno' => 'MOLINA']);
    Licencia::factory()->create(['ci' => '7633685', 'motivo' => 'VACACION']);

    $pantallas = [
        route('roles.index'),
        route('usuarios.index'),
        route('horarios.list'),
        route('dias-excepcionales.list'),
        route('licencias.list'),
    ];

    foreach ($pantallas as $url) {
        $this->get($url)
            ->assertOk()
            ->assertSee('$store.eliminar.abrir(', escape: false)
            ->assertDontSee('onsubmit="return confirm(', escape: false);
    }
});

test('cada botón de baja lleva la URL de su destroy y el detalle de lo que se borra', function () {
    $rol = Role::create(['name' => 'operador', 'guard_name' => 'web']);

    $this->get(route('roles.index'))
        ->assertOk()
        // La URL va escapada por @js: se compara igual que la renderiza Blade.
        ->assertSee(str_replace('/', '\/', route('roles.destroy', $rol)), escape: false)
        ->assertSee('Se elimina el rol', escape: false);
});

test('en el menú «Mas» la baja del usuario queda en el bloque rojo', function () {
    User::factory()->create(['name' => 'Ignacio Molina']);

    $this->get(route('usuarios.index'))
        ->assertOk()
        ->assertSee('class="dropdown-menu__peligro"', escape: false)
        ->assertSee('Se elimina el usuario', escape: false);
});
