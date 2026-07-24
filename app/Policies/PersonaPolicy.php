<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Persona;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Autorización de los funcionarios locales (MySQL, tabla `personas`). Usa los
 * mismos permisos que la policy legada (ViewAny:Persona, etc.), así los roles
 * no cambian al pasar la vista de la conexión `sia` a la base local.
 */
class PersonaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Persona');
    }

    public function view(AuthUser $authUser, Persona $persona): bool
    {
        return $authUser->can('View:Persona');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Persona');
    }

    public function update(AuthUser $authUser, Persona $persona): bool
    {
        return $authUser->can('Update:Persona');
    }

    public function delete(AuthUser $authUser, Persona $persona): bool
    {
        return $authUser->can('Delete:Persona');
    }
}
