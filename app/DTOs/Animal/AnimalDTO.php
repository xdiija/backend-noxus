<?php

namespace App\DTOs\Animal;

use App\Http\Requests\Animal\StoreUpdateAnimalRequest;

class AnimalDTO
{
    /**
     * @param  int[]|null  $tags  Tag ids to sync, or null to leave untouched.
     * @param  array|null  $rescue  Nested 1:1 intake payload, or null.
     */
    public function __construct(
        public readonly string $name,
        public readonly int $species_id,
        public readonly ?int $breed_id,
        public readonly ?string $sex,
        public readonly ?string $estimated_birth_date,
        public readonly bool $birth_date_is_estimated,
        public readonly int $size_id,
        public readonly ?string $color,
        public readonly ?string $microchip,
        public readonly bool $neutered,
        public readonly ?string $neuter_date,
        public readonly int $status_id,
        public readonly ?string $notes,
        public readonly ?array $tags,
        public readonly ?array $rescue,
    ) {}

    public static function fromRequest(StoreUpdateAnimalRequest $request): self
    {
        $data = $request->validated();

        return new self(
            name: $data['name'],
            species_id: (int) $data['species_id'],
            breed_id: isset($data['breed_id']) ? (int) $data['breed_id'] : null,
            sex: $data['sex'] ?? null,
            estimated_birth_date: $data['estimated_birth_date'] ?? null,
            birth_date_is_estimated: (bool) $data['birth_date_is_estimated'],
            size_id: (int) $data['size_id'],
            color: $data['color'] ?? null,
            microchip: $data['microchip'] ?? null,
            neutered: (bool) $data['neutered'],
            neuter_date: $data['neuter_date'] ?? null,
            status_id: (int) $data['status_id'],
            notes: $data['notes'] ?? null,
            tags: $data['tags'] ?? null,
            rescue: $data['rescue'] ?? null,
        );
    }

    /**
     * Model-ready payload for `animals` (no organization_id — the Service sets
     * it from the authenticated user; no tags/rescue — handled separately).
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'species_id' => $this->species_id,
            'breed_id' => $this->breed_id,
            'sex' => $this->sex,
            'estimated_birth_date' => $this->estimated_birth_date,
            'birth_date_is_estimated' => $this->birth_date_is_estimated,
            'size_id' => $this->size_id,
            'color' => $this->color,
            'microchip' => $this->microchip,
            'neutered' => $this->neutered,
            'neuter_date' => $this->neutered ? $this->neuter_date : null,
            'status_id' => $this->status_id,
            'notes' => $this->notes,
        ];
    }
}
