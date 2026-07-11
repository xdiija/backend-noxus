<?php

namespace App\DTOs\Adopter;

use App\Http\Requests\Adopter\StoreUpdateAdopterRequest;

class AdopterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $cpf,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $address,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly ?string $birth_date,
    ) {}

    public static function fromRequest(StoreUpdateAdopterRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            cpf: $data['cpf'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            birth_date: $data['birth_date'] ?? null,
        );
    }

    /**
     * Model-ready payload for `adopters` (no organization_id — the Service sets
     * it from the authenticated user).
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'cpf' => $this->cpf,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'birth_date' => $this->birth_date,
        ];
    }
}
