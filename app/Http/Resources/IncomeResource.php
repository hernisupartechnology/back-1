<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource de Ingreso con categoría y recibos opcionales.
 */
class IncomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'budget_period_id'   => $this->budget_period_id,
            'user_id'            => $this->user_id,
            'income_category_id' => $this->income_category_id,
            'description'        => $this->description,
            'amount'             => (float) $this->amount,
            'received_date'      => $this->received_date?->format('Y-m-d'),
            'notes'              => $this->notes,
            'has_receipts'       => $this->whenLoaded('receipts', fn () => $this->receipts->count() > 0),
            'receipts_count'     => $this->whenCounted('receipts'),
            'created_at'         => $this->created_at?->toISOString(),

            'category' => $this->whenLoaded('category', fn () => [
                'id'    => $this->category->id,
                'name'  => $this->category->name,
                'icon'  => $this->category->icon,
                'color' => $this->category->color,
            ]),
            'receipts' => ReceiptResource::collection($this->whenLoaded('receipts')),
        ];
    }
}
