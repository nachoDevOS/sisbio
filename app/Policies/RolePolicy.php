<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Fuente única de la convención de nombres de permiso ("Habilidad:Modelo",
     * separador ':', case pascal). La usan el seeder de roles/permisos y
     * RoleController (matriz de checkboxes).
     *
     * @var array<string, string> modelo => etiqueta legible
     */
    public const MODELOS = [
        'Equipo' => 'Equipos',
        'User' => 'Usuarios',
        'Role' => 'Roles',
        'Persona' => 'Funcionarios',
        'Asistencia' => 'Marcaciones',
        'DiaTurno' => 'Horarios',
    ];

    /**
     * @var array<string, string> habilidad => etiqueta legible
     */
    public const ABILIDADES = [
        'ViewAny' => 'Ver listado',
        'View' => 'Ver ficha',
        'Create' => 'Crear',
        'Update' => 'Editar',
        'Delete' => 'Eliminar',
    ];

    /**
     * Todos los nombres de permiso posibles, en el orden Modelo → Habilidad.
     *
     * @return list<string>
     */
    public static function nombresDePermiso(): array
    {
        $nombres = [];

        foreach (self::MODELOS as $modelo => $etiquetaModelo) {
            foreach (self::ABILIDADES as $habilidad => $etiquetaHabilidad) {
                $nombres[] = "{$habilidad}:{$modelo}";
            }
        }

        return $nombres;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Role');
    }

    public function view(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('View:Role');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Role');
    }

    public function update(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Update:Role');
    }

    public function delete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Delete:Role');
    }
}
