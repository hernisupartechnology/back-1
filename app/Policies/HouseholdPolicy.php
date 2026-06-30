<?php

namespace App\Policies;

use App\Models\Household;
use App\Models\User;

/**
 * Policy de acceso al Hogar.
 *
 * Reglas de negocio:
 * - Solo el owner puede gestionar miembros, categorías y configuración del hogar.
 * - Cualquier miembro del hogar puede ver su información.
 * - Solo el owner puede cerrar períodos y gestionar configuración.
 */
class HouseholdPolicy
{
    /**
     * Verificar si el usuario es miembro del hogar.
     */
    public function view(User $usuario, Household $hogar): bool
    {
        return $usuario->household_id === $hogar->id;
    }

    /**
     * Solo el owner puede actualizar el hogar.
     */
    public function update(User $usuario, Household $hogar): bool
    {
        return $usuario->household_id === $hogar->id && $usuario->isOwner();
    }

    /**
     * Solo el owner puede invitar miembros.
     */
    public function invite(User $usuario, Household $hogar): bool
    {
        return $usuario->household_id === $hogar->id && $usuario->isOwner();
    }

    /**
     * Solo el owner puede cambiar roles.
     */
    public function updateRole(User $usuario, Household $hogar): bool
    {
        return $usuario->household_id === $hogar->id && $usuario->isOwner();
    }

    /**
     * Solo el owner puede eliminar miembros.
     */
    public function removeMember(User $usuario, Household $hogar): bool
    {
        return $usuario->household_id === $hogar->id && $usuario->isOwner();
    }

    /**
     * Solo el owner puede transferir la propiedad.
     */
    public function transferOwnership(User $usuario, Household $hogar): bool
    {
        return $hogar->owner_id === $usuario->id;
    }
}
