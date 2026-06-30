<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource de Gasto con información de pago, categoría, dueño y quien lo registró.
 */
class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'budget_period_id'     => $this->budget_period_id,
            'user_id'              => $this->user_id,
            'registered_by'        => $this->registered_by,
            'expense_category_id'  => $this->expense_category_id,
            'description'          => $this->description,
            'amount'               => (float) $this->amount,
            'due_date'             => $this->due_date?->format('Y-m-d'),
            'paid_date'            => $this->paid_date?->format('Y-m-d'),
            'is_paid'              => $this->is_paid,
            'payment_type'         => $this->payment_type,
            'partial_amount'       => $this->partial_amount ? (float) $this->partial_amount : null,
            'is_recurring'         => $this->is_recurring,
            'recurrence_frequency' => $this->recurrence_frequency,
            'notes'                => $this->notes,
            'has_receipts'         => $this->whenLoaded('receipts', fn () => $this->receipts->count() > 0),
            'receipts_count'       => $this->whenCounted('receipts'),
            'created_at'           => $this->created_at?->toISOString(),

            'category' => $this->whenLoaded('category', fn () => [
                'id'    => $this->category->id,
                'name'  => $this->category->name,
                'icon'  => $this->category->icon,
                'color' => $this->category->color,
                'type'  => $this->category->type,
            ]),
            'owner' => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'registered_by_user' => $this->whenLoaded('registeredBy', fn () => [
                'id'   => $this->registeredBy->id,
                'name' => $this->registeredBy->name,
            ]),
            'receipts' => ReceiptResource::collection($this->whenLoaded('receipts')),
        ];
    }
}
