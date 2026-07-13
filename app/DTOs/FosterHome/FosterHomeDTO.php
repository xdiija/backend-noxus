<?php

namespace App\DTOs\FosterHome;

use App\Http\Requests\FosterHome\StoreUpdateFosterHomeRequest;

class FosterHomeDTO
{
    public function __construct(
        public readonly string $responsible_name,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly ?int $capacity,
    ) {}

    public static function fromRequest(StoreUpdateFosterHomeRequest $request): self
    {
        $data = $request->validated();

        return new self(
            responsible_name: $data['responsible_name'],
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            capacity: isset($data['capacity']) ? (int) $data['capacity'] : null,
        );
    }

    /**
     * Model-ready payload for `foster_homes` (no organization_id — the Service
     * sets it from the authenticated user).
     */
    public function toArray(): array
    {
        return [
            'responsible_name' => $this->responsible_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'capacity' => $this->capacity,
        ];
    }
}
