<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(asSuperAdmin());
});

test('al crear un usuario hashea la contraseña y asigna el rol', function () {
    $rol = Role::firstOrCreate(['name' => 'Tecnico', 'guard_name' => 'web']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Nuevo Operador',
            'email' => 'operador@sisbio.test',
            'password' => 'secreta123',
            'roles' => [$rol->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(UserResource::getUrl('index'));

    $usuario = User::where('email', 'operador@sisbio.test')->first();

    expect($usuario)->not->toBeNull()
        ->and(Hash::check('secreta123', $usuario->password))->toBeTrue()
        ->and($usuario->hasRole('Tecnico'))->toBeTrue();
});

test('al editar sin contraseña conserva la actual', function () {
    $usuario = User::factory()->create(['password' => Hash::make('original123')]);

    Livewire::test(EditUser::class, ['record' => $usuario->getRouteKey()])
        ->fillForm([
            'name' => 'Nombre Editado',
            'password' => '',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $usuario->refresh();

    expect($usuario->name)->toBe('Nombre Editado')
        ->and(Hash::check('original123', $usuario->password))->toBeTrue();
});

test('el correo debe ser único', function () {
    User::factory()->create(['email' => 'repetido@sisbio.test']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Otro',
            'email' => 'repetido@sisbio.test',
            'password' => 'secreta123',
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);
});
