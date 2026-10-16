<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Helpers compartidos por las Policies de Filament.
 *
 * Define quién puede ver y quién puede gestionar (crear/editar/borrar)
 * cada recurso, según los roles de Spatie:
 *
 * - `administrador`: acceso total en todos los recursos.
 * - `jefe de turno`: gestiona Turnos, Asistencias y Vacaciones.
 * - `contador`: gestiona Gastos, Ingresos y Liquidaciones.
 * - `solo lectura`: solo puede consultar.
 */
trait RolesDeAcceso
{
    /**
     * Todos los roles con acceso al panel pueden consultar.
     */
    private function puedeVer(User $user): bool
    {
        return $user->hasAnyRole(User::ROLES_PANEL);
    }

    /**
     * El administrador siempre puede gestionar, además de los roles indicados.
     *
     * @param  array<int, string>  $roles
     */
    private function puedeGestionar(User $user, array $roles): bool
    {
        return $user->hasRole('administrador')
            || $user->hasAnyRole($roles);
    }

    /**
     * Solo el administrador puede gestionar este recurso.
     */
    private function soloAdministrador(User $user): bool
    {
        return $user->hasRole('administrador');
    }
}
