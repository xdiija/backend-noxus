<?php

namespace App\DTOs\Donor;

use App\Http\Requests\Donor\StoreUpdateDonorRequest;

class DonorDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $notes,
    ) {}

    public static function fromRequest(StoreUpdateDonorRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Model-ready payload for `donors` (no organization_id — the Service sets
     * it from the authenticated user).
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'notes' => $this->notes,
        ];
    }
}
