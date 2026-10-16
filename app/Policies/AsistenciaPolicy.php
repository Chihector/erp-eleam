<?php

namespace App\Policies;

use App\Models\Asistencia;
use App\Models\User;
use App\Policies\Concerns\RolesDeAcceso;

class AsistenciaPolicy
{
    use RolesDeAcceso;

    public function viewAny(User $user): bool
    {
        return $this->puedeVer($user);
    }

    public function view(User $user, Asistencia $record): bool
    {
        return $this->puedeVer($user);
    }

    public function create(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function update(User $user, Asistencia $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function delete(User $user, Asistencia $record): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }

    public function deleteAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['jefe de turno']);
    }
}
