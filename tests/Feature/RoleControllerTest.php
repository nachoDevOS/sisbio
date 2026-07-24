<?php

use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RolePolicy::nombresDePermiso() as $nombre) {
        Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
    }

    $this->actingAs(asSuperAdmin());
});

test('el listado muestra los roles con su cantidad de permisos', function () {
    $rol = Role::create(['name' => 'operador', 'guard_name' => 'web']);
    $rol->syncPermissions(['ViewAny:Equipo', 'View:Equipo']);

    $this->get(route('roles.index'))
        ->assertOk()
        ->assertSee('operador')
        ->assertSee('2');
});

test('crea un rol nuevo con los permisos elegidos', function () {
    $this->post(route('roles.store'), [
        'name' => 'operador',
        'permisos' => ['ViewAny:Equipo', 'Update:Equipo'],
    ])
        ->assertRedirect(route('roles.index'))
        ->assertSessionHas('estado');

    $rol = Role::where('name', 'operador')->first();

    expect($rol)->not->toBeNull()
        ->and($rol->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['Update:Equipo', 'ViewAny:Equipo']);
});

test('el formulario de edición marca los permisos actuales', function () {
    $rol = Role::create(['name' => 'operador', 'guard_name' => 'web']);
    $rol->syncPermissions(['ViewAny:Equipo']);

    $this->get(route('roles.edit', $rol))
        ->assertOk()
        ->assertSee('checked', escape: false);
});

test('actualiza los permisos de un rol existente', function () {
    $rol = Role::create(['name' => 'operador', 'guard_name' => 'web']);
    $rol->syncPermissions(['ViewAny:Equipo']);

    $this->put(route('roles.update', $rol), [
        'name' => 'operador',
        'permisos' => ['ViewAny:User'],
    ])->assertRedirect(route('roles.index'));

    expect($rol->refresh()->permissions->pluck('name')->all())->toBe(['ViewAny:User']);
});

test('elimina un rol de forma lógica', function () {
    $rol = Role::create(['name' => 'temporal', 'guard_name' => 'web']);

    $this->delete(route('roles.destroy', $rol))
        ->assertRedirect(route('roles.index'));

    // Borrado lógico: desaparece de las consultas normales pero sigue en la
    // base marcado con deleted_at.
    expect(Role::where('name', 'temporal')->exists())->toBeFalse()
        ->and(Role::onlyTrashed()->where('name', 'temporal')->exists())->toBeTrue();
});

test('nunca se puede eliminar el rol super_admin', function () {
    $superAdmin = Role::where('name', 'super_admin')->first();

    $this->delete(route('roles.destroy', $superAdmin))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Role::where('name', 'super_admin')->exists())->toBeTrue();
});

test('un usuario sin permiso no puede entrar al listado de roles', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('roles.index'))->assertForbidden();
});
