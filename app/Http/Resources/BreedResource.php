<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BreedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'species_id' => $this->species_id,
            'species' => new SpeciesResource($this->whenLoaded('species')),
            // false = system default (read-only for the org); true = org's own.
            'is_custom' => $this->organization_id !== null,
        ];
    }
}
