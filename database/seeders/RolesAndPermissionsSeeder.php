<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Reproduce en cualquier entorno (dev, staging, CI) los permisos y el rol
 * super_admin que hoy solo existen porque alguien corrió `shield:generate`
 * y `shield:super-admin` a mano contra la base de desarrollo. Sin este
 * seeder, un `migrate:fresh --seed` no deja el panel usable.
 *
 * Los nombres de permiso replican la convención de Filament Shield
 * (config/filament-shield.php: separador ':', case pascal) para los
 * modelos con policy hoy: Equipo, User, Role, Persona y Asistencia.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    private const ABILITIES = ['ViewAny', 'View', 'Create', 'Update', 'Delete'];

    private const MODELOS = ['Equipo', 'User', 'Role', 'Persona', 'Asistencia'];

    public function run(): void
    {
        foreach (self::MODELOS as $modelo) {
            foreach (self::ABILITIES as $habilidad) {
                Permission::firstOrCreate([
                    'name' => "{$habilidad}:{$modelo}",
                    'guard_name' => 'web',
                ]);
            }
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions(Permission::all());
    }
}
