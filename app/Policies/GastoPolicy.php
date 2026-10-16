<?php

namespace App\Policies;

use App\Models\Gasto;
use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class GastoPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->puedeVer($user);
    }

    public function view(User $user, Gasto $record): bool
    {
        return $this->puedeVer($user);
    }

    public function create(User $user): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function update(User $user, Gasto $record): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function delete(User $user, Gasto $record): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function deleteAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }
}
