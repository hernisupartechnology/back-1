<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

/**
 * Policy de acceso a Gastos.
 *
 * Reglas:
 * - Owner: acceso total a gastos del hogar.
 * - Member: ve y gestiona sus propios gastos + los de viewers que supervisa.
 * - Viewer: SOLO puede ver sus propios gastos — NUNCA crear/editar/eliminar.
 */
class ExpensePolicy
{
    /**
     * Un viewer NUNCA puede crear gastos (validado en Policy, no solo en frontend).
     */
    public function create(User $usuario): bool
    {
        return ! $usuario->isViewer();
    }

    /**
     * Ver un gasto: el dueño, quien lo registró, o el owner del hogar.
     */
    public function view(User $usuario, Expense $gasto): bool
    {
        if ($usuario->isOwner() && $usuario->household_id === $gasto->budgetPeriod->household_id) {
            return true;
        }

        // El dueño del gasto puede verlo
        if ($gasto->user_id === $usuario->id) {
            return true;
        }

        // Quien lo registró puede verlo (padre que registró gasto del hijo)
        if ($gasto->registered_by === $usuario->id) {
            return true;
        }

        return false;
    }

    /**
     * Actualizar: solo quien lo registró o el owner.
     */
    public function update(User $usuario, Expense $gasto): bool
    {
        if ($usuario->isViewer()) {
            return false;
        }

        if ($usuario->isOwner()) {
            return $usuario->household_id === $gasto->budgetPeriod->household_id;
        }

        return $gasto->registered_by === $usuario->id;
    }

    /**
     * Eliminar: solo quien lo registró o el owner.
     */
    public function delete(User $usuario, Expense $gasto): bool
    {
        return $this->update($usuario, $gasto);
    }
}
