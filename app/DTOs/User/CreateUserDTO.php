<?php

namespace App\DTOs\User;

use App\Http\Requests\User\StoreUpdateUserRequestRequest;

class CreateUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly int $status,
        public readonly array $roles,
    ) {}

    public static function fromRequest(StoreUpdateUserRequestRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            status: (int) $data['status'],
            roles: $data['roles'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'status' => $this->status,
        ];
    }
}
