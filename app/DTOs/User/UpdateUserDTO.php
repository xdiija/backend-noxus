<?php

namespace App\DTOs\User;

use App\Http\Requests\User\StoreUpdateUserRequestRequest;

class UpdateUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $password,
        public readonly int $status,
        public readonly int $role,
    ) {}

    public static function fromRequest(StoreUpdateUserRequestRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'] ?? null,
            status: (int) $data['status'],
            role: (int) $data['role'],
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'role_id' => $this->role,
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        return $data;
    }
}
