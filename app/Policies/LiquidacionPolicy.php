<?php

namespace App\Policies;

use App\Models\Liquidacion;
use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class LiquidacionPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->puedeVer($user);
    }

    public function view(User $user, Liquidacion $record): bool
    {
        return $this->puedeVer($user);
    }

    public function create(User $user): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function update(User $user, Liquidacion $record): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function delete(User $user, Liquidacion $record): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function deleteAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }
}
