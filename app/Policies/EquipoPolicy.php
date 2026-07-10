<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Equipo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EquipoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Equipo');
    }

    public function view(AuthUser $authUser, Equipo $equipo): bool
    {
        return $authUser->can('View:Equipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Equipo');
    }

    public function update(AuthUser $authUser, Equipo $equipo): bool
    {
        return $authUser->can('Update:Equipo');
    }

    public function delete(AuthUser $authUser, Equipo $equipo): bool
    {
        return $authUser->can('Delete:Equipo');
    }
}
