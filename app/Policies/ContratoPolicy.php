<?php

namespace App\Policies;

use App\Models\Contrato;
use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class ContratoPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->puedeVer($user);
    }

    public function view(User $user, Contrato $record): bool
    {
        return $this->puedeVer($user);
    }

    public function create(User $user): bool
    {
        return $this->soloAdministrador($user);
    }

    public function update(User $user, Contrato $record): bool
    {
        return $this->soloAdministrador($user);
    }

    public function delete(User $user, Contrato $record): bool
    {
        return $this->soloAdministrador($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->soloAdministrador($user);
    }
}
