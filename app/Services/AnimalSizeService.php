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
        // organization_id comes from the authenticated user, never from input.
        return AnimalSize::create($dto->toArray() + [
            'organization_id' => $this->organizationId(),
        ]);
    }

    public function update(string $id, AnimalSizeDTO $dto): AnimalSize
    {
        $size = $this->owned()->findOrFail($id);
        $size->update($dto->toArray());

        return $size;
    }

    public function delete(string $id): void
    {
        $size = $this->owned()->findOrFail($id);
        $size->delete();
    }

    /**
     * Readable rows: system defaults + the org's own entries (ADR-0004).
     */
    private function readable(): Builder
    {
        $orgId = $this->organizationId();

        return AnimalSize::query()->where(function (Builder $q) use ($orgId) {
            $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
        });
    }

    /**
     * Writable rows: only the org's own custom entries. System defaults
     * (organization_id NULL) are shared and must never be edited or deleted
     * by a tenant — those queries simply won't match, yielding a 404.
     */
    private function owned(): Builder
    {
        return AnimalSize::query()->where('organization_id', $this->organizationId());
    }

    private function organizationId(): ?int
    {
        return auth()->user()?->organization_id;
    }
}
