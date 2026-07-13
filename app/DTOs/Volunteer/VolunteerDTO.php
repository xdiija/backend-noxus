<?php

namespace App\DTOs\Volunteer;

use App\Http\Requests\Volunteer\StoreUpdateVolunteerRequest;

class VolunteerDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $role,
        public readonly int $status,
    ) {}

    public static function fromRequest(StoreUpdateVolunteerRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            role: $data['role'] ?? null,
            status: (int) $data['status'],
        );
    }

    /**
     * Model-ready payload for `volunteers` (no organization_id — the Service
     * sets it from the authenticated user).
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
        ];
    }
}
