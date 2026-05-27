<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'stage' => $this->stage->value,
            'hunger' => $this->hunger,
            'happiness' => $this->happiness,
            'energy' => $this->energy,
            'cleanliness' => $this->cleanliness,
            'health' => $this->health,
            'weight' => $this->weight,
            'age_minutes' => $this->age_minutes,
            'age_readable' => $this->age_readable,
            'is_alive' => $this->is_alive,
            'is_sleeping' => $this->is_sleeping,
            'born_at' => $this->born_at?->toIso8601String(),
            'last_interaction_at' => $this->last_interaction_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
