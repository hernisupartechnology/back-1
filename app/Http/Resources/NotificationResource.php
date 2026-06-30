<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource de Notificación in-app.
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'title'      => $this->title,
            'body'       => $this->body,
            'data'       => $this->data,
            'is_read'    => $this->isRead(),
            'read_at'    => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
