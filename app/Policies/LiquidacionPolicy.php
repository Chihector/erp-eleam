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
        // Una liquidación cerrada es inmutable hasta que un administrador
        // la reabra con un motivo registrado.
        return ! $record->cerrada
            && $this->puedeGestionar($user, ['contador']);
    }

    public function delete(User $user, Liquidacion $record): bool
    {
        return ! $record->cerrada
            && $this->puedeGestionar($user, ['contador']);
    }

    public function deleteAny(User $user): bool
    {
        return $this->puedeGestionar($user, ['contador']);
    }

    public function cerrar(User $user, Liquidacion $record): bool
    {
        return ! $record->cerrada
            && $this->puedeGestionar($user, ['contador']);
    }

    public function reabrir(User $user, Liquidacion $record): bool
    {
        return $record->cerrada
            && $this->soloAdministrador($user);
    }
}
