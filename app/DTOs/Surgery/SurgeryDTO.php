<?php

namespace App\DTOs\Surgery;

use App\Http\Requests\Surgery\StoreUpdateSurgeryRequest;

class SurgeryDTO
{
    public function __construct(
        public readonly string $surgery_date,
        public readonly string $procedure,
        public readonly ?string $veterinarian,
        public readonly ?string $clinic,
        public readonly ?string $cost,
        public readonly ?string $notes,
        public readonly bool $is_castration,
    ) {}

    public static function fromRequest(StoreUpdateSurgeryRequest $request): self
    {
        $data = $request->validated();

        return new self(
            surgery_date: $data['surgery_date'],
            procedure: $data['procedure'],
            veterinarian: $data['veterinarian'] ?? null,
            clinic: $data['clinic'] ?? null,
            cost: isset($data['cost']) ? (string) $data['cost'] : null,
            notes: $data['notes'] ?? null,
            is_castration: (bool) ($data['is_castration'] ?? false),
        );
    }

    /**
     * Model-ready payload for `surgeries` (no animal_id/organization_id — the
     * Service sets both from the parent animal). `is_castration` is not a
     * column: it tells the Service to sync animals.neutered/neuter_date.
     */
    public function toArray(): array
    {
        return [
            'surgery_date' => $this->surgery_date,
            'procedure' => $this->procedure,
            'veterinarian' => $this->veterinarian,
            'clinic' => $this->clinic,
            'cost' => $this->cost,
            'notes' => $this->notes,
        ];
    }
}
