<?php

namespace App\Policies;

use App\Models\Finiquito;
use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class FiniquitoPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->puedeVer($user);
    }

    public function view(User $user, Finiquito $record): bool
    {
        return $this->puedeVer($user);
    }

    public function create(User $user): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function update(User $user, Finiquito $record): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function delete(User $user, Finiquito $record): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function deleteAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }
}
