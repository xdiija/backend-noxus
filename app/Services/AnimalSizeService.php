<?php

namespace App\Services;

use App\DTOs\AnimalSize\AnimalSizeDTO;
use App\Models\AnimalSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AnimalSizeService
{
    /**
     * System defaults (organization_id NULL) plus this org's own custom sizes.
     */
    public function list(): Collection
    {
        return $this->readable()->orderBy('sort_order')->orderBy('name')->get();
    }

    public function find(string $id): AnimalSize
    {
        return $this->readable()->findOrFail($id);
    }

    public function create(AnimalSizeDTO $dto): AnimalSize
    {
        // Noxus-managed global reference (ADR-0008): shared across all orgs, so
        // no organization_id. The `noxus` middleware guards this endpoint.
        return AnimalSize::create($dto->toArray() + [
            'organization_id' => null,
        ]);
    }

    public function update(string $id, AnimalSizeDTO $dto): AnimalSize
    {
        $size = $this->writable()->findOrFail($id);
        $size->update($dto->toArray());

        return $size;
    }

    public function delete(string $id): void
    {
        $size = $this->writable()->findOrFail($id);
        $size->delete();
    }

    /**
     * Readable rows: the shared global entries (organization_id NULL). The
     * org-scoped predicate is retained defensively (ADR-0004 history).
     */
    private function readable(): Builder
    {
        $orgId = $this->organizationId();

        return AnimalSize::query()->where(function (Builder $q) use ($orgId) {
            $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
        });
    }

    /**
     * Writable rows: the shared global entries, managed directly by Noxus
     * (ADR-0008).
     */
    private function writable(): Builder
    {
        return AnimalSize::query()->whereNull('organization_id');
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
