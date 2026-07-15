<?php

namespace App\DTOs\Kennel;

use App\Http\Requests\Kennel\StoreUpdateKennelRequest;

class KennelDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $capacity,
        public readonly ?int $size_id,
        public readonly ?int $status_id,
        public readonly ?string $notes,
    ) {}

    public static function fromRequest(StoreUpdateKennelRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            capacity: (int) $data['capacity'],
            size_id: isset($data['size_id']) ? (int) $data['size_id'] : null,
            status_id: isset($data['status_id']) ? (int) $data['status_id'] : null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Model-ready payload for `kennels` (no organization_id — the Service sets
     * it from the authenticated user; a null status_id falls back to the
     * is_default lookup row in the Service).
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'capacity' => $this->capacity,
            'size_id' => $this->size_id,
            'notes' => $this->notes,
        ];
    }
}
