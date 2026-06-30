<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource de Usuario para respuestas JSON estandarizadas.
 * Nunca expone password ni remember_token.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'avatar'      => $this->avatar
                ? asset('storage/' . $this->avatar)
                : null,
            'role'        => $this->role,
            'is_minor'    => $this->is_minor,
            'phone'       => $this->phone,
            'birthdate'   => $this->birthdate?->format('Y-m-d'),
            'household_id'  => $this->household_id,
            'supervised_by' => $this->supervised_by,
            'created_at'    => $this->created_at?->toISOString(),

            // Relaciones opcionales (solo se incluyen si están cargadas)
            'household'   => $this->whenLoaded('household', fn () => [
                'id'   => $this->household->id,
                'name' => $this->household->name,
            ]),
            'supervisor'  => $this->whenLoaded('supervisor', fn () => [
                'id'   => $this->supervisor->id,
                'name' => $this->supervisor->name,
            ]),
        ];
    }
}
