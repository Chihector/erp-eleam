<?php

namespace App\Policies;

use App\Models\ParametroLegal;
use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class ParametroLegalPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->soloAdministrador($user);
    }

    public function view(User $user, ParametroLegal $record): bool
    {
        return $this->soloAdministrador($user);
    }

    public function create(User $user): bool
    {
        return $this->soloAdministrador($user);
    }

    public function update(User $user, ParametroLegal $record): bool
    {
        return $this->soloAdministrador($user);
    }

    public function delete(User $user, ParametroLegal $record): bool
    {
        return $this->soloAdministrador($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->soloAdministrador($user);
    }
}
