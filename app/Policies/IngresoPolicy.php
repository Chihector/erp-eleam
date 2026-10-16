<?php

namespace App\Policies;

use App\Models\Ingreso;
use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class IngresoPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->puedeVer($user);
    }

    public function view(User $user, Ingreso $record): bool
    {
        return $this->puedeVer($user);
    }

    public function create(User $user): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function update(User $user, Ingreso $record): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function delete(User $user, Ingreso $record): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function deleteAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }
}
