<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource de Recibo — nunca expone la ruta real del archivo,
 * sino una URL de descarga a través del controlador autenticado.
 */
class ReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'expense_id'   => $this->expense_id,
            'income_id'    => $this->income_id,
            'user_id'      => $this->user_id,
            'file_name'    => $this->file_name,
            'file_type'    => $this->file_type,
            'file_size'    => $this->file_size,
            'file_size_formatted' => $this->file_size_formatted,
            'description'  => $this->description,
            'uploaded_at'  => $this->uploaded_at?->toISOString(),
            // URL de vista a través del controlador autenticado (no URL pública)
            'view_url'     => route('api.receipts.show', $this->id),
            'thumbnail_url' => $this->thumbnail_path
                ? route('api.receipts.thumbnail', $this->id)
                : null,
        ];
    }
}
