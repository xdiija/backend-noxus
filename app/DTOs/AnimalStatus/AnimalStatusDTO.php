<?php

namespace App\DTOs\AnimalStatus;

use App\Http\Requests\AnimalStatus\StoreUpdateAnimalStatusRequest;

class AnimalStatusDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $color,
        public readonly int $sort_order,
        public readonly bool $is_default,
    ) {}

    public static function fromRequest(StoreUpdateAnimalStatusRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            color: $data['color'] ?? null,
            sort_order: (int) ($data['sort_order'] ?? 0),
            is_default: (bool) ($data['is_default'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'color' => $this->color,
            'sort_order' => $this->sort_order,
            'is_default' => $this->is_default,
        ];
    }
}
