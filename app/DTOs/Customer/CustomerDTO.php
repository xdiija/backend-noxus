<?php

namespace App\DTOs\Customer;

use App\Http\Requests\Customer\StoreUpdateCustomerRequest;

class CustomerDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $cpf,
        public readonly string $phone_1,
        public readonly string $phone_2,
        public readonly int $status,
    ) {}

    public static function fromRequest(StoreUpdateCustomerRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            email: $data['email'],
            cpf: $data['cpf'],
            phone_1: $data['phone_1'],
            phone_2: $data['phone_2'],
            status: (int) $data['status'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'cpf' => $this->cpf,
            'phone_1' => $this->phone_1,
            'phone_2' => $this->phone_2,
            'status' => $this->status,
        ];
    }
}
