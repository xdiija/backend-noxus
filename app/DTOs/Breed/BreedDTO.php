<?php

namespace App\DTOs\Breed;

use App\Http\Requests\Breed\StoreUpdateBreedRequest;

class BreedDTO
{
    public function __construct(
        public readonly int $species_id,
        public readonly string $name,
    ) {}

    public static function fromRequest(StoreUpdateBreedRequest $request): self
    {
        $data = $request->validated();

        return new self(
            species_id: (int) $data['species_id'],
            name: $data['name'],
        );
    }

    public function toArray(): array
    {
        return [
            'species_id' => $this->species_id,
            'name' => $this->name,
        ];
    }
}
