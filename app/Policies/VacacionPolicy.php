<?php

namespace App\Policies;

use App\Models\Vacacion;
use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class VacacionPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->puedeVer($user);
    }

    public function view(User $user, Vacacion $record): bool
    {
        return $this->puedeVer($user);
    }

    public function create(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function update(User $user, Vacacion $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function delete(User $user, Vacacion $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function deleteAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function forceDelete(User $user, Vacacion $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function restore(User $user, Vacacion $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function restoreAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }
}
