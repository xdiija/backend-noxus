<?php

namespace App\DTOs\KennelStay;

use App\Http\Requests\KennelStay\StoreUpdateKennelStayRequest;

class KennelStayDTO
{
    public function __construct(
        public readonly int $animal_id,
        public readonly string $entry_date,
        public readonly ?string $reason,
        public readonly ?string $notes,
    ) {}

    public static function fromRequest(StoreUpdateKennelStayRequest $request): self
    {
        $data = $request->validated();

        return new self(
            animal_id: (int) $data['animal_id'],
            entry_date: $data['entry_date'],
            reason: $data['reason'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Model-ready payload for `animal_kennel_stays` (no kennel_id — the Service
     * resolves it from the org-scoped parent route).
     */
    public function toArray(): array
    {
        return [
            'animal_id' => $this->animal_id,
            'entry_date' => $this->entry_date,
            'reason' => $this->reason,
            'notes' => $this->notes,
        ];
    }
}
