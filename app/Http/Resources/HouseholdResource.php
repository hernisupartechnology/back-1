<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource del hogar con miembros opcionales.
 */
class HouseholdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'currency'    => $this->currency,
            'avatar'      => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'owner_id'    => $this->owner_id,
            'created_at'  => $this->created_at?->toISOString(),

            'owner'   => $this->whenLoaded('owner', fn () => [
                'id'   => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'members_count' => $this->whenCounted('members'),
        ];
    }
}
