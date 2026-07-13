<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        ->assertNotified('Creado')
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

test('usuarios comparte grupo de navegación con roles', function () {
    expect(UserResource::getNavigationGroup())
        ->toBe(RoleResource::getNavigationGroup());
});

test('crear usuario no ofrece "crear y crear otro"', function () {
    expect((new CreateUser)->canCreateAnother())->toBeFalse()
        ->and(CreateAction::make()->canCreateAnother())->toBeFalse();
});

test('el usuario puede subir su foto de avatar', function () {
    Storage::fake('public');

    $usuario = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $usuario->getRouteKey()])
        ->fillForm(['avatar_path' => UploadedFile::fake()->image('foto.jpg')])
        ->call('save')
        ->assertHasNoFormErrors();

    $usuario->refresh();

    expect($usuario->avatar_path)->toStartWith('avatars/')
        ->and($usuario->getFilamentAvatarUrl())->toContain($usuario->avatar_path);

    Storage::disk('public')->assertExists($usuario->avatar_path);
});

test('sin foto el avatar usa las iniciales como respaldo', function () {
    $usuario = User::factory()->create(['avatar_path' => null]);

    expect($usuario->getFilamentAvatarUrl())->toBeNull()
        ->and(filament()->getUserAvatarUrl($usuario))->toContain('ui-avatars.com');
});

test('las tablas del panel usan filas cebra', function () {
    User::factory()->count(2)->create();

    Livewire::test(ListUsers::class)
        ->assertSeeHtml('fi-striped');
});

test('el selector "por página" aparece arriba de la tabla', function () {
    Livewire::test(ListUsers::class)
        ->assertSeeHtml('siscor-per-page-top')
        ->assertSeeHtml('wire:model.live="tableRecordsPerPage"');
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
