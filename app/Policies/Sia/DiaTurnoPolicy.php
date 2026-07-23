<?php

declare(strict_types=1);

namespace App\Policies\Sia;

use App\Models\Sia\DiaTurno;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DiaTurnoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DiaTurno');
    }

    public function view(AuthUser $authUser, DiaTurno $diaTurno): bool
    {
        return $authUser->can('View:DiaTurno');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DiaTurno');
    }

    public function update(AuthUser $authUser, DiaTurno $diaTurno): bool
    {
        return $authUser->can('Update:DiaTurno');
    }

    public function delete(AuthUser $authUser, DiaTurno $diaTurno): bool
    {
        return $authUser->can('Delete:DiaTurno');
    }
}
