<?php

namespace App\Policies;

use App\Models\LicenciaMedica;
use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class LicenciaMedicaPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->puedeVer($user);
    }

    public function view(User $user, LicenciaMedica $record): bool
    {
        return $this->puedeVer($user);
    }

    public function create(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function update(User $user, LicenciaMedica $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function delete(User $user, LicenciaMedica $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function deleteAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }
}
