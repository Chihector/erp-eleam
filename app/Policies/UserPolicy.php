<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class UserPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->soloAdministrador($user);
    }

    public function view(User $user, User $record): bool
    {
        return $this->soloAdministrador($user);
    }

    public function create(User $user): bool
    {
        return $this->soloAdministrador($user);
    }

    public function update(User $user, User $record): bool
    {
        return $this->soloAdministrador($user);
    }

    public function delete(User $user, User $record): bool
    {
        return $this->soloAdministrador($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->soloAdministrador($user);
    }
}
