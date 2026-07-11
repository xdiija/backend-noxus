<?php

namespace App\DTOs\Adoption;

use App\Http\Requests\Adoption\StoreUpdateAdoptionRequest;

class AdoptionDTO
{
    public function __construct(
        public readonly int $animal_id,
        public readonly int $adopter_id,
        public readonly string $adoption_date,
        public readonly ?int $status_id,
        public readonly bool $agreement_signed,
        public readonly ?string $notes,
    ) {}

    public static function fromRequest(StoreUpdateAdoptionRequest $request): self
    {
        $data = $request->validated();

        return new self(
            animal_id: (int) $data['animal_id'],
            adopter_id: (int) $data['adopter_id'],
            adoption_date: $data['adoption_date'],
            status_id: isset($data['status_id']) ? (int) $data['status_id'] : null,
            agreement_signed: (bool) ($data['agreement_signed'] ?? false),
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Model-ready payload for `adoptions` (no organization_id / status_id — the
     * Service sets those; return fields are managed by the return workflow).
     */
    public function toArray(): array
    {
        return [
            'animal_id' => $this->animal_id,
            'adopter_id' => $this->adopter_id,
            'adoption_date' => $this->adoption_date,
            'agreement_signed' => $this->agreement_signed,
            'notes' => $this->notes,
        ];
    }
}
