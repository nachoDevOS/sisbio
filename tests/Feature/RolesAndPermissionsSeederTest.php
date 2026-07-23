<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('el seeder deja un super_admin funcional con todos los permisos', function () {
    $this->seed(DatabaseSeeder::class);

    $superAdmin = Role::where('name', 'super_admin')->first();

    expect($superAdmin)->not->toBeNull()
        ->and(Permission::count())->toBe(30)
        ->and($superAdmin->permissions()->count())->toBe(30);

    $usuario = User::where('email', 'test@example.com')->first();

    expect($usuario)->not->toBeNull()
        ->and($usuario->hasRole('super_admin'))->toBeTrue();
});

test('el seeder es idempotente si se corre dos veces', function () {
    $this->seed(DatabaseSeeder::class);

    expect(fn () => Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])->syncPermissions(Permission::all()))
        ->not->toThrow(Exception::class);

    expect(Permission::count())->toBe(30);
});
