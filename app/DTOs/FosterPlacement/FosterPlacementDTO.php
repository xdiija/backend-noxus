<?php

namespace App\DTOs\FosterPlacement;

use App\Http\Requests\FosterPlacement\StoreUpdateFosterPlacementRequest;

class FosterPlacementDTO
{
    public function __construct(
        public readonly int $animal_id,
        public readonly string $entry_date,
    ) {}

    public static function fromRequest(StoreUpdateFosterPlacementRequest $request): self
    {
        $data = $request->validated();

        return new self(
            animal_id: (int) $data['animal_id'],
            entry_date: $data['entry_date'],
        );
    }

    /**
     * Model-ready payload for `animal_foster_homes` (no foster_home_id — the
     * Service resolves it from the org-scoped parent route).
     */
    public function toArray(): array
    {
        return [
            'animal_id' => $this->animal_id,
            'entry_date' => $this->entry_date,
        ];
    }
}
