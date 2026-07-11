<?php

namespace App\DTOs\AdoptionFollowUp;

use App\Http\Requests\AdoptionFollowUp\StoreUpdateAdoptionFollowUpRequest;

class AdoptionFollowUpDTO
{
    public function __construct(
        public readonly string $follow_up_date,
        public readonly ?string $contact_method,
        public readonly int $status_id,
        public readonly ?string $notes,
    ) {}

    public static function fromRequest(StoreUpdateAdoptionFollowUpRequest $request): self
    {
        $data = $request->validated();

        return new self(
            follow_up_date: $data['follow_up_date'],
            contact_method: $data['contact_method'] ?? null,
            status_id: (int) $data['status_id'],
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Model-ready payload for `adoption_follow_ups` (no adoption_id — the Service
     * sets it from the parent route).
     */
    public function toArray(): array
    {
        return [
            'follow_up_date' => $this->follow_up_date,
            'contact_method' => $this->contact_method,
            'status_id' => $this->status_id,
            'notes' => $this->notes,
        ];
    }
}
