<?php

namespace Database\Seeders;

use App\Policies\RolePolicy;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Reproduce en cualquier entorno (dev, staging, CI) los permisos y el rol
 * super_admin que hoy solo existen porque alguien corrió `shield:generate`
 * y `shield:super-admin` a mano contra la base de desarrollo. Sin este
 * seeder, un `migrate:fresh --seed` no deja el panel usable.
 *
 * La lista de permisos vive en RolePolicy::nombresDePermiso() (misma fuente
 * que usa RoleController para la matriz de checkboxes).
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RolePolicy::nombresDePermiso() as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions(Permission::all());
    }
}
