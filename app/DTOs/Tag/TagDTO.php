<?php

namespace App\DTOs\Tag;

use App\Http\Requests\Tag\StoreUpdateTagRequest;

class TagDTO
{
    public function __construct(
        public readonly string $name,
    ) {}

    public static function fromRequest(StoreUpdateTagRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
