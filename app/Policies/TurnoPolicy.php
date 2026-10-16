<?php

namespace App\Policies;

use App\Models\Turno;
use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class TurnoPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->puedeVer($user);
    }

    public function view(User $user, Turno $record): bool
    {
        return $this->puedeVer($user);
    }

    public function create(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function update(User $user, Turno $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function delete(User $user, Turno $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function deleteAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function forceDelete(User $user, Turno $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function restore(User $user, Turno $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function restoreAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }
}
