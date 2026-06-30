<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource del período de presupuesto con totales calculados.
 */
class BudgetPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'household_id' => $this->household_id,
            'user_id'      => $this->user_id,
            'year'         => $this->year,
            'month'        => $this->month,
            'label'        => $this->label, // "Junio 2026"
            'notes'        => $this->notes,
            'is_closed'    => $this->is_closed,
            'created_at'   => $this->created_at?->toISOString(),

            // Totales (solo si las relaciones están cargadas)
            'total_incomes'  => $this->whenLoaded('incomes',
                fn () => $this->incomes->sum('amount')
            ),
            'total_expenses' => $this->whenLoaded('expenses',
                fn () => $this->expenses->sum('amount')
            ),
        ];
    }
}
