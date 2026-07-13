<?php

namespace App\DTOs\HealthRecord;

use App\Http\Requests\HealthRecord\StoreUpdateHealthRecordRequest;

class HealthRecordDTO
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $description,
        public readonly string $event_date,
        public readonly ?string $veterinarian,
        public readonly ?string $notes,
    ) {}

    public static function fromRequest(StoreUpdateHealthRecordRequest $request): self
    {
        $data = $request->validated();

        return new self(
            type: $data['type'],
            description: $data['description'] ?? null,
            event_date: $data['event_date'],
            veterinarian: $data['veterinarian'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Model-ready payload for `health_records` (no animal_id/organization_id —
     * the Service sets both from the parent animal).
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description,
            'event_date' => $this->event_date,
            'veterinarian' => $this->veterinarian,
            'notes' => $this->notes,
        ];
    }
}
