<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource de Meta de Ahorro con progreso calculado.
 */
class SavingsGoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'household_id'        => $this->household_id,
            'user_id'             => $this->user_id,
            'name'                => $this->name,
            'target_amount'       => (float) $this->target_amount,
            'current_amount'      => (float) $this->current_amount,
            'remaining_amount'    => $this->remaining_amount,
            'progress_percentage' => $this->progress_percentage,
            'deadline'            => $this->deadline?->format('Y-m-d'),
            'color'               => $this->color,
            'icon'                => $this->icon,
            'status'              => $this->status,
            'created_at'          => $this->created_at?->toISOString(),

            'contributions' => $this->whenLoaded('contributions', fn () =>
                $this->contributions->map(fn ($c) => [
                    'id'                => $c->id,
                    'amount'            => (float) $c->amount,
                    'contribution_date' => $c->contribution_date?->format('Y-m-d'),
                    'notes'             => $c->notes,
                    'user'              => ['id' => $c->user_id, 'name' => optional($c->user)->name],
                ])
            ),
        ];
    }
}
