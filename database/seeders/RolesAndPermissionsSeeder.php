<?php

namespace Database\Seeders;

use App\Policies\RolePolicy;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Reproduce en cualquier entorno (dev, staging, CI) los permisos y el rol
 * super_admin. Sin este seeder, un `migrate:fresh --seed` deja el sistema sin
 * accesos configurados.
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
