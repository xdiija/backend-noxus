<?php

namespace App\DTOs\Organization;

use App\Http\Requests\Organization\StoreUpdateOrganizationRequest;

class OrganizationDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $legal_name,
        public readonly ?string $tax_id,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $website,
        public readonly ?string $address,
        public readonly ?string $city,
        public readonly ?string $state,
    ) {}

    public static function fromRequest(StoreUpdateOrganizationRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            legal_name: $data['legal_name'] ?? null,
            tax_id: $data['tax_id'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            website: $data['website'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'tax_id' => $this->tax_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
        ];
    }
}
