<?php

namespace App\Services;

use App\DTOs\Breed\BreedDTO;
use App\Models\Breed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BreedService
{
    /**
     * System defaults (organization_id NULL) plus this org's own breeds,
     * optionally narrowed to a single species for the Animal form's cascade.
     */
    public function list(?int $speciesId = null): Collection
    {
        return $this->readable()
            ->when($speciesId, fn (Builder $q) => $q->where('species_id', $speciesId))
            ->with('species')
            ->orderBy('name')
            ->get();
    }

    public function find(string $id): Breed
    {
        return $this->readable()->with('species')->findOrFail($id);
    }

    public function create(BreedDTO $dto): Breed
    {
        // Noxus-managed global reference (ADR-0008): shared across all orgs, so
        // no organization_id. The `noxus` middleware guards this endpoint.
        $breed = Breed::create($dto->toArray() + [
            'organization_id' => null,
        ]);

        return $breed->load('species');
    }

    public function update(string $id, BreedDTO $dto): Breed
    {
        $breed = $this->writable()->findOrFail($id);
        $breed->update($dto->toArray());

        return $breed->load('species');
    }

    public function delete(string $id): void
    {
        $breed = $this->writable()->findOrFail($id);
        $breed->delete();
    }

    /**
     * Readable rows: the shared global entries (organization_id NULL). The
     * org-scoped predicate is retained defensively (ADR-0004 history).
     */
    private function readable(): Builder
    {
        $orgId = $this->organizationId();

        return Breed::query()->where(function (Builder $q) use ($orgId) {
            $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
        });
    }

    /**
     * Writable rows: the shared global entries, managed directly by Noxus
     * (ADR-0008).
     */
    private function writable(): Builder
    {
        return Breed::query()->whereNull('organization_id');
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
