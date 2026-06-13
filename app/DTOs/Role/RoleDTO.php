<?php

namespace App\DTOs\Role;

use App\Enums\Status;
use App\Http\Requests\Role\StoreUpdateRoleRequest;

class RoleDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $status,
        public readonly array $permissions,
    ) {}

    public static function fromRequest(StoreUpdateRoleRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            status: (int) ($data['status'] ?? Status::ACTIVE->value),
            permissions: $data['permissions'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status,
        ];
    }
}
