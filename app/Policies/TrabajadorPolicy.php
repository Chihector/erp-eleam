<?php

namespace App\Policies;

use App\Models\Trabajador;
use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class TrabajadorPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->puedeVer($user);
    }

    public function view(User $user, Trabajador $record): bool
    {
        return $this->puedeVer($user);
    }

    public function create(User $user): bool
    {
        return $this->soloAdministrador($user);
    }

    public function update(User $user, Trabajador $record): bool
    {
        return $this->soloAdministrador($user);
    }

    public function delete(User $user, Trabajador $record): bool
    {
        return $this->soloAdministrador($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->soloAdministrador($user);
    }
}
