<?php

declare(strict_types=1);

namespace App\Policies\Sia;

use App\Models\Sia\Persona;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

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
