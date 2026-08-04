<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        // Sin `User::factory()`: las factories usan `fake()`, que viene de
        // fakerphp/faker —dependencia de desarrollo—, y la imagen de producción
        // se arma con `composer install --no-dev`. Ahí esto moría con
        // «Call to undefined function Database\Factories\fake()», justo en
        // medio del `migrate:fresh --seed` de MigrarSiaSeeder.
        $clave = Str::password(16);

        $usuario = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Admin', 'password' => Hash::make($clave)],
        );

        $usuario->assignRole('super_admin');

        // La clave solo se muestra cuando el usuario se acaba de crear: en una
        // corrida repetida la contraseña vigente es la que ya tenía.
        if ($usuario->wasRecentlyCreated) {
            $this->command?->newLine();
            $this->command?->warn("Usuario «admin@admin.com» creado con la clave: {$clave}");
            $this->command?->warn('Anotala y cambiala apenas entres: no se vuelve a mostrar.');
        }
    }
}
