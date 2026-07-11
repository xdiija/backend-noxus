<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sex' => $this->sex,
            'color' => $this->color,
            'microchip' => $this->microchip,
            'neutered' => $this->neutered,
            'neuter_date' => DateHelper::toBR($this->neuter_date),
            'estimated_birth_date' => DateHelper::toBR($this->estimated_birth_date),
            'birth_date_is_estimated' => $this->birth_date_is_estimated,
            // Derived from estimated_birth_date, never stored (ADR-0006).
            'age' => $this->age,
            'notes' => $this->notes,
            'species' => new SpeciesResource($this->whenLoaded('species')),
            'breed' => new BreedResource($this->whenLoaded('breed')),
            'size' => new AnimalSizeResource($this->whenLoaded('size')),
            'status' => new AnimalStatusResource($this->whenLoaded('status')),
            'rescue' => new RescueResource($this->whenLoaded('rescue')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            // Only loaded on show() (each URL is a presigned link), never on list().
            'images' => AnimalImageResource::collection($this->whenLoaded('images')),
            'updated_at' => DateHelper::toBR($this->updated_at),
            'created_at' => DateHelper::toBR($this->created_at),
        ];
    }
}
