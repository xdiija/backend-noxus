<?php

namespace App\DTOs\Menu;

use App\Enums\Status;
use App\Http\Requests\Menu\StoreUpdateMenuRequest;

class MenuDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $key,
        public readonly ?string $route,
        public readonly ?int $parent_id,
        public readonly string $icon,
        public readonly int $order,
        public readonly int $status,
        public readonly bool $exclusive_noxus,
    ) {}

    public static function fromRequest(StoreUpdateMenuRequest $request): self
    {
        $data = $request->validated();

        return new self(
            // Key is only present on create; on update the request prohibits it.
            name: $data['name'],
            key: $data['key'] ?? null,
            route: $data['route'] ?? null,
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            icon: $data['icon'],
            order: (int) ($data['order'] ?? 0),
            status: (int) ($data['status'] ?? Status::ACTIVE->value),
            exclusive_noxus: (bool) $data['exclusive_noxus'],
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'route' => $this->route,
            'parent_id' => $this->parent_id,
            'icon' => $this->icon,
            'order' => $this->order,
            'status' => $this->status,
            'exclusive_noxus' => $this->exclusive_noxus,
        ];

        // Key is immutable: only included on create.
        if ($this->key !== null) {
            $data['key'] = $this->key;
        }

        return $data;
    }
}
