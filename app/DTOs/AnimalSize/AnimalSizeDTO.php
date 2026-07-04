<?php

namespace App\DTOs\AnimalSize;

use App\Http\Requests\AnimalSize\StoreUpdateAnimalSizeRequest;

class AnimalSizeDTO
{
    public function __construct(
        public readonly string $name,
        public readonly int $sort_order,
    ) {}

    public static function fromRequest(StoreUpdateAnimalSizeRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            sort_order: (int) ($data['sort_order'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'sort_order' => $this->sort_order,
        ];
    }
}
